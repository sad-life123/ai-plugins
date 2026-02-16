<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace aiplacement_chat;

defined('MOODLE_INTERNAL') || die();

class context {
    
    /**
     * ГЛАВНЫЙ МЕТОД - собирает ВСЕ данные о курсе
     */
    public function get_course_context(int $courseid, int $userid = 0): string {
        global $DB;
        
        $context_parts = [];
        $sources = get_config('coursechat', 'context_sources') ?: [];
        
        // ============================================
        // 1. 📚 СТРУКТУРА КУРСА (темы, секции)
        // ============================================
        if (!empty($sources['sections'])) {
            $sections = $DB->get_records('course_sections', 
                ['course' => $courseid], 
                'section', 
                'section, name, summary'
            );
            
            $section_texts = [];
            foreach ($sections as $section) {
                if ($section->section == 0) continue; // Пропускаем общую секцию
                
                $text = "Тема {$section->section}";
                if (!empty($section->name)) {
                    $text .= ": {$section->name}";
                }
                if (!empty(strip_tags($section->summary))) {
                    $text .= " - " . strip_tags($section->summary);
                }
                $section_texts[] = $text;
            }
            
            if (!empty($section_texts)) {
                $context_parts[] = "📚 СТРУКТУРА КУРСА:\n" . implode("\n", $section_texts);
            }
        }
        
        // ============================================
        // 2. 📝 АКТИВНОСТИ (задания, тесты, форумы)
        // ============================================
        if (!empty($sources['activities'])) {
            $modules = $DB->get_records_sql("
                SELECT cm.id, m.name as modname, cm.instance, 
                       cm.section, cs.name as section_name
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {course_sections} cs ON cs.id = cm.section
                WHERE cm.course = ? AND m.visible = 1
                ORDER BY cs.section, cm.section
            ", [$courseid]);
            
            $activity_texts = [];
            foreach ($modules as $mod) {
                // Получаем имя активности из соответствующей таблицы
                $instance = $DB->get_record($mod->modname, ['id' => $mod->instance], 'name, intro');
                
                if ($instance) {
                    $text = "- {$mod->modname}: {$instance->name}";
                    if (!empty($instance->intro)) {
                        $intro = strip_tags($instance->intro);
                        $intro = substr($intro, 0, 200);
                        $text .= " - {$intro}...";
                    }
                    $activity_texts[] = $text;
                }
            }
            
            if (!empty($activity_texts)) {
                $context_parts[] = "📝 АКТИВНОСТИ КУРСА:\n" . implode("\n", $activity_texts);
            }
        }
        
        // ============================================
        // 3. 📄 ФАЙЛЫ КУРСА (PDF, DOCX, TXT)
        // ============================================
        if (!empty($sources['files'])) {
            $fs = get_file_storage();
            $context = \context_course::instance($courseid);
            
            $files = $fs->get_area_files(
                $context->id,
                'course',
                'overviewfiles',
                0,
                'timecreated DESC',
                false
            );
            
            // + файлы из ресурсов
            $resource_files = $DB->get_records_sql("
                SELECT f.id, f.filename, f.filesize, f.mimetype
                FROM {files} f
                JOIN {context} ctx ON ctx.id = f.contextid
                WHERE ctx.contextlevel = 70
                AND ctx.instanceid IN (
                    SELECT id FROM {course_modules} WHERE course = ?
                )
                AND f.component = 'mod_resource'
                AND f.filearea = 'content'
                AND f.filesize > 0
            ", [$courseid]);
            
            $file_texts = [];
            
            // Обрабатываем файлы курса
            foreach ($files as $file) {
                $filename = $file->get_filename();
                if ($file->get_filesize() > 0 && !$file->is_directory()) {
                    $file_texts[] = "📄 {$filename}";
                    
                    // Парсим текст из PDF/DOCX/TXT (асинхронно, сохраняем в БД)
                    $this->process_file_async($file, $courseid);
                }
            }
            
            // Добавляем ранее извлеченный текст из файлов
            $extracted_texts = $DB->get_records('coursechat_file_cache', 
                ['courseid' => $courseid], 
                'timecreated DESC', 
                'filename, content', 
                0, 5 // Топ-5 файлов
            );
            
            foreach ($extracted_texts as $extracted) {
                $content = substr($extracted->content, 0, 500);
                $file_texts[] = "📄 {$extracted->filename}:\n{$content}...";
            }
            
            if (!empty($file_texts)) {
                $context_parts[] = "📄 ФАЙЛЫ КУРСА:\n" . implode("\n\n", $file_texts);
            }
        }
        
        // ============================================
        // 4. 📊 ОЦЕНКИ СТУДЕНТА (личный контекст)
        // ============================================
        if (!empty($sources['grades']) && $userid > 0) {
            $grades = $DB->get_records_sql("
                SELECT gi.itemname, gg.finalgrade, gg.rawgrademax
                FROM {grade_grades} gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE gg.userid = ? AND gi.courseid = ?
                AND gg.finalgrade IS NOT NULL
                ORDER BY gg.timemodified DESC
            ", [$userid, $courseid]);
            
            $grade_texts = [];
            foreach ($grades as $grade) {
                $percentage = round(($grade->finalgrade / $grade->rawgrademax) * 100);
                $grade_texts[] = "- {$grade->itemname}: {$grade->finalgrade}/{$grade->rawgrademax} ({$percentage}%)";
            }
            
            if (!empty($grade_texts)) {
                $context_parts[] = "📊 ВАШИ ОЦЕНКИ:\n" . implode("\n", $grade_texts);
            }
        }
        
        // Склеиваем всё в один текст
        $full_context = implode("\n\n", $context_parts);
        
        // Ограничиваем длину
        $max_length = get_config('coursechat', 'max_context_length') ?: 8000;
        if (strlen($full_context) > $max_length) {
            $full_context = substr($full_context, 0, $max_length) . "...";
        }
        
        return $full_context;
    }
    
    /**
     * Асинхронная обработка файлов (PDF, DOCX, TXT)
     */
    private function process_file_async($file, int $courseid) {
        global $DB;
        
        $filename = $file->get_filename();
        $filesize = $file->get_filesize();
        
        // Проверяем, обрабатывали ли уже этот файл
        $existing = $DB->get_record('coursechat_file_cache', [
            'courseid' => $courseid,
            'filename' => $filename,
            'contenthash' => $file->get_contenthash()
        ]);
        
        if ($existing) {
            return;
        }
        
        // Обрабатываем только текстовые форматы
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $content = '';
        
        try {
            if ($ext === 'txt' || $ext === 'text') {
                $content = $file->get_content();
            } elseif ($ext === 'pdf') {
                $content = $this->parse_pdf($file);
            } elseif ($ext === 'docx') {
                $content = $this->parse_docx($file);
            }
            
            if (!empty($content)) {
                $cache = new \stdClass();
                $cache->courseid = $courseid;
                $cache->filename = $filename;
                $cache->contenthash = $file->get_contenthash();
                $cache->content = $content;
                $cache->timecreated = time();
                
                $DB->insert_record('coursechat_file_cache', $cache);
            }
            
        } catch (\Exception $e) {
            debugging("Error parsing file {$filename}: " . $e->getMessage());
        }
    }
    
    /**
     * Парсинг PDF
     */
    private function parse_pdf($file): string {
        $content = $file->get_content();
        
        // Пытаемся использовать pdftotext если доступен
        $tempdir = make_temp_directory('coursechat');
        $tempfile = $tempdir . '/' . uniqid() . '.pdf';
        file_put_contents($tempfile, $content);
        
        $output = '';
        
        if (exec('which pdftotext')) {
            $outputfile = $tempdir . '/' . uniqid() . '.txt';
            exec("pdftotext -nopgbrk '{$tempfile}' '{$outputfile}'");
            if (file_exists($outputfile)) {
                $output = file_get_contents($outputfile);
                unlink($outputfile);
            }
        } else {
            // Fallback: просто говорим что файл есть
            $output = "[PDF файл доступен в курсе]";
        }
        
        unlink($tempfile);
        
        return substr($output, 0, 2000);
    }
    
    /**
     * Парсинг DOCX
     */
    private function parse_docx($file): string {
        $content = $file->get_content();
        
        // Простой парсинг DOCX (zip с XML)
        $tempdir = make_temp_directory('coursechat');
        $tempfile = $tempdir . '/' . uniqid() . '.docx';
        file_put_contents($tempfile, $content);
        
        $output = '';
        
        try {
            $zip = new \ZipArchive();
            if ($zip->open($tempfile) === true) {
                if ($xml = $zip->getFromName('word/document.xml')) {
                    // Грязный парсинг текста из XML
                    $output = strip_tags($xml);
                }
                $zip->close();
            }
        } catch (\Exception $e) {
            $output = "[DOCX файл доступен в курсе]";
        }
        
        unlink($tempfile);
        
        return substr($output, 0, 2000);
    }
}