<?php
// /ai/placement/quizgen/classes/question_bank.php

namespace aiplacement_quizgen;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

class question_bank {
    
    /**
     * Сохранить вопрос в банк вопросов Moodle
     */
    public function save_to_bank(array $question_data, int $category_id = 0, int $courseid = 0): ?int {
        global $DB, $USER;
        
        try {
            // 1. Получаем категорию
            if (empty($category_id)) {
                if ($courseid) {
                    $category = question_get_default_category($courseid);
                    $category_id = $category->id ?? 0;
                }
                
                if (empty($category_id)) {
                    $category = $this->get_default_category();
                    $category_id = $category->id;
                }
            }
            
            // 2. Создаем объект вопроса
            $question = new \stdClass();
            $question->name = '[AI] ' . mb_substr($question_data['question'], 0, 50) . (mb_strlen($question_data['question']) > 50 ? '...' : '');
            $question->questiontext = $question_data['question'];
            $question->questiontextformat = FORMAT_HTML;
            $question->generalfeedback = $question_data['explanation'] ?? '';
            $question->generalfeedbackformat = FORMAT_HTML;
            $question->defaultmark = 1.0;
            $question->penalty = 0.3333333;
            $question->hidden = 0;
            $question->timecreated = time();
            $question->timemodified = time();
            $question->createdby = $USER->id;
            $question->modifiedby = $USER->id;
            $question->category = $category_id;
            $question->stamp = make_unique_id_code();
            $question->version = make_unique_id_code();
            
            // 3. В зависимости от типа вопроса
            switch ($question_data['type']) {
                case 'multichoice':
                    $question->qtype = 'multichoice';
                    $question->options = new \stdClass();
                    $question->options->single = 1; // Один правильный
                    $question->options->shuffleanswers = 1;
                    $question->options->answernumbering = 'abc';
                    $question->options->showstandardinstruction = 0;
                    
                    $question->options->answers = [];
                    foreach ($question_data['options'] as $index => $option) {
                        $answer = new \stdClass();
                        $answer->answer = $option;
                        $answer->fraction = ($index == $question_data['correct']) ? 1.0 : 0.0;
                        $answer->feedback = '';
                        $answer->feedbackformat = FORMAT_HTML;
                        $question->options->answers[] = $answer;
                    }
                    break;
                    
                case 'truefalse':
                    $question->qtype = 'truefalse';
                    $question->options = new \stdClass();
                    $question->options->trueanswer = 0;
                    $question->options->falseanswer = 1;
                    
                    $question->options->answers = [];
                    
                    $true = new \stdClass();
                    $true->answer = 'Верно';
                    $true->fraction = ($question_data['correct'] == 0) ? 1.0 : 0.0;
                    $true->feedback = '';
                    $true->feedbackformat = FORMAT_HTML;
                    $question->options->answers[] = $true;
                    
                    $false = new \stdClass();
                    $false->answer = 'Неверно';
                    $false->fraction = ($question_data['correct'] == 1) ? 1.0 : 0.0;
                    $false->feedback = '';
                    $false->feedbackformat = FORMAT_HTML;
                    $question->options->answers[] = $false;
                    break;
                    
                case 'shortanswer':
                    $question->qtype = 'shortanswer';
                    $question->options = new \stdClass();
                    $question->options->usecase = 0;
                    
                    $question->options->answers = [];
                    
                    $answer = new \stdClass();
                    $answer->answer = $question_data['correctanswer'] ?? $question_data['options'][0] ?? '';
                    $answer->fraction = 1.0;
                    $answer->feedback = $question_data['explanation'] ?? '';
                    $answer->feedbackformat = FORMAT_HTML;
                    $question->options->answers[] = $answer;
                    break;
                    
                case 'essay':
                    $question->qtype = 'essay';
                    $question->options = new \stdClass();
                    $question->options->responseformat = 'editor';
                    $question->options->responserequired = 1;
                    $question->options->responsefieldlines = 15;
                    $question->options->attachments = 0;
                    $question->options->attachmentsrequired = 0;
                    $question->options->graderinfo = $question_data['rubric'] ?? $question_data['explanation'] ?? '';
                    $question->options->graderinfoformat = FORMAT_HTML;
                    $question->options->responsetemplate = '';
                    $question->options->responsetemplateformat = FORMAT_HTML;
                    break;
            }
            
            // 4. Сохраняем вопрос
            $questionid = question_save_question($question, $category_id);
            
            // 5. Добавляем теги
            if (!empty($question_data['tags'])) {
                $this->add_tags($questionid, $question_data['tags']);
            }
            
            return $questionid;
            
        } catch (\Exception $e) {
            debugging('Error saving question: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }
    
    /**
     * Сохранить несколько вопросов
     */
    public function save_multiple(array $questions, int $category_id = 0, int $courseid = 0): array {
        $saved = [];
        $failed = [];
        
        foreach ($questions as $question) {
            $id = $this->save_to_bank($question, $category_id, $courseid);
            if ($id) {
                $saved[] = $id;
            } else {
                $failed[] = $question['question'];
            }
        }
        
        return [
            'success' => count($saved),
            'failed' => count($failed),
            'question_ids' => $saved
        ];
    }
    
    /**
     * Получить категорию по умолчанию
     */
    private function get_default_category() {
        global $DB;
        
        $category = $DB->get_record('question_categories', [
            'name' => 'AI Generated Questions',
            'parent' => 0
        ]);
        
        if (!$category) {
            $category = new \stdClass();
            $category->name = 'AI Generated Questions';
            $category->info = 'Автоматически сгенерированные AI вопросы';
            $category->contextid = \context_system::instance()->id;
            $category->parent = 0;
            $category->sortorder = 999;
            $category->stamp = make_unique_id_code();
            $category->idnumber = 'ai_questions';
            $category->timecreated = time();
            $category->timemodified = time();
            
            $category->id = $DB->insert_record('question_categories', $category);
        }
        
        return $category;
    }
    
    /**
     * Добавить теги к вопросу
     */
    private function add_tags(int $questionid, array $tags) {
        global $DB;
        
        if (empty($tags)) {
            return;
        }
        
        $context = \context_system::instance();
        
        foreach ($tags as $tagname) {
            $tag = \core_tag_tag::get_or_create($tagname, $context);
            \core_tag_tag::add_item_tag('core_question', 'question', $questionid, $context, $tagname);
        }
    }
}