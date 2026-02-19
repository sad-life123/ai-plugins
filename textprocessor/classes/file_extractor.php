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

namespace aiplacement_textprocessor;

defined('MOODLE_INTERNAL') || die();

/**
 * File extractor for PDF and DOCX files.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_extractor {

    /**
     * Extract text from a file.
     *
     * @param string $filepath Path to the file
     * @param string $filename Original filename (to determine type)
     * @return string Extracted text
     */
    public static function extract_text(string $filepath, string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'pdf':
                return self::extract_pdf($filepath);
            case 'docx':
                return self::extract_docx($filepath);
            case 'doc':
                return self::extract_doc($filepath);
            case 'txt':
            case 'text':
                return self::clean_text(file_get_contents($filepath));
            case 'rtf':
                return self::extract_rtf($filepath);
            default:
                return '';
        }
    }

    /**
     * Extract text from base64 encoded file content.
     * File is NOT stored on server - processed in memory via temp file and immediately deleted.
     *
     * @param string $base64content Base64 encoded file content
     * @param string $filename Original filename
     * @return string Extracted text
     */
    public static function extract_from_base64(string $base64content, string $filename): string {
        $tempdir = make_temp_directory('textprocessor');
        $tempfile = $tempdir . '/' . uniqid('extract_') . '_' . basename($filename);

        $content = base64_decode($base64content);
        file_put_contents($tempfile, $content);

        $text = self::extract_text($tempfile, $filename);

        // Clean up temp file immediately - no server storage.
        @unlink($tempfile);

        return $text;
    }

    /**
     * Extract text from PDF file.
     *
     * @param string $filepath Path to PDF file
     * @return string Extracted text
     */
    public static function extract_pdf(string $filepath): string {
        $text = '';

        // Try pdftotext command line tool first.
        if (self::command_exists('pdftotext')) {
            $tempdir = make_temp_directory('textprocessor');
            $outputfile = $tempdir . '/' . uniqid('pdf_') . '.txt';

            exec("pdftotext -nopgbrk -layout " . escapeshellarg($filepath) . " " . escapeshellarg($outputfile), $output, $returncode);

            if ($returncode === 0 && file_exists($outputfile)) {
                $text = file_get_contents($outputfile);
                @unlink($outputfile);
            }
        }

        // Fallback: try to extract using PHP (basic approach).
        if (empty($text)) {
            $text = self::extract_pdf_php($filepath);
        }

        return self::clean_text($text);
    }

    /**
     * Basic PDF text extraction using PHP.
     * This is a fallback when pdftotext is not available.
     *
     * @param string $filepath Path to PDF file
     * @return string Extracted text
     */
    private static function extract_pdf_php(string $filepath): string {
        $content = file_get_contents($filepath);
        $text = '';

        // Simple regex to extract text between BT and ET tags.
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
            foreach ($matches[1] as $match) {
                // Extract text from Tj and TJ operators.
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $match, $textmatches)) {
                    $text .= implode(' ', $textmatches[1]) . ' ';
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $match, $arraymatches)) {
                    foreach ($arraymatches[1] as $arr) {
                        if (preg_match_all('/\((.*?)\)/', $arr, $arrtext)) {
                            $text .= implode('', $arrtext[1]);
                        }
                    }
                }
            }
        }

        // If still empty, try a more aggressive approach.
        if (empty($text)) {
            $text = preg_replace('/[^\x20-\x7E\x0A\x0D]/', ' ', $content);
            $text = preg_replace('/\s+/', ' ', $text);
        }

        return $text;
    }

    /**
     * Extract text from DOCX file.
     *
     * @param string $filepath Path to DOCX file
     * @return string Extracted text
     */
    public static function extract_docx(string $filepath): string {
        $text = '';

        try {
            $zip = new \ZipArchive();
            if ($zip->open($filepath) === true) {
                $content = $zip->getFromName('word/document.xml');

                if ($content !== false) {
                    $xml = simplexml_load_string($content);

                    if ($xml !== false) {
                        $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

                        // Extract paragraphs with structure.
                        $paragraphs = $xml->xpath('//w:p');
                        if ($paragraphs) {
                            $paratexts = [];
                            foreach ($paragraphs as $p) {
                                $ptext = '';
                                $tnodes = $p->xpath('.//w:t');
                                if ($tnodes) {
                                    foreach ($tnodes as $t) {
                                        $ptext .= (string)$t;
                                    }
                                }
                                if (!empty(trim($ptext))) {
                                    $paratexts[] = $ptext;
                                }
                            }
                            if (!empty($paratexts)) {
                                $text = implode("\n", $paratexts);
                            }
                        }
                    }
                }

                $zip->close();
            }
        } catch (\Exception $e) {
            debugging('DOCX extraction error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return self::clean_text($text);
    }

    /**
     * Extract text from DOC file (legacy Word format).
     *
     * @param string $filepath Path to DOC file
     * @return string Extracted text
     */
    public static function extract_doc(string $filepath): string {
        $text = '';

        // Try antiword if available.
        if (self::command_exists('antiword')) {
            exec("antiword " . escapeshellarg($filepath), $output, $returncode);
            if ($returncode === 0) {
                $text = implode("\n", $output);
            }
        }

        // Fallback: try catdoc.
        if (empty($text) && self::command_exists('catdoc')) {
            exec("catdoc " . escapeshellarg($filepath), $output, $returncode);
            if ($returncode === 0) {
                $text = implode("\n", $output);
            }
        }

        // Last resort: extract readable ASCII text.
        if (empty($text)) {
            $content = file_get_contents($filepath);
            $text = preg_replace('/[^\x20-\x7E\x0A\x0D\xC0-\xFF]/', ' ', $content);
            $text = preg_replace('/\s+/', ' ', $text);
        }

        return self::clean_text($text);
    }

    /**
     * Extract text from RTF file.
     *
     * @param string $filepath Path to RTF file
     * @return string Extracted text
     */
    public static function extract_rtf(string $filepath): string {
        $content = file_get_contents($filepath);

        // Simple RTF text extraction.
        $text = preg_replace('/\\\\[a-z]+\d* ?/i', '', $content);
        $text = preg_replace('/\\\\[^a-z]/i', '', $text);
        $text = str_replace(['{', '}'], '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return self::clean_text($text);
    }

    /**
     * Clean extracted text.
     *
     * @param string $text Raw text
     * @return string Cleaned text
     */
    private static function clean_text(string $text): string {
        // Remove null bytes.
        $text = str_replace("\0", '', $text);

        // Normalize line endings.
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove excessive whitespace.
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Trim.
        $text = trim($text);

        // Limit length to prevent memory issues.
        $maxlength = 50000;
        if (strlen($text) > $maxlength) {
            $text = substr($text, 0, $maxlength) . '...';
        }

        return $text;
    }

    /**
     * Check if a command exists on the system.
     *
     * @param string $command Command name
     * @return bool True if command exists
     */
    private static function command_exists(string $command): bool {
        $return = shell_exec(sprintf("which %s 2>/dev/null", escapeshellarg($command)));
        return !empty($return);
    }

    /**
     * Get supported file types.
     *
     * @return array Array of supported extensions
     */
    public static function get_supported_types(): array {
        return ['pdf', 'docx', 'doc', 'txt', 'text', 'rtf'];
    }

    /**
     * Check if a file type is supported.
     *
     * @param string $filename Filename to check
     * @return bool True if supported
     */
    public static function is_supported(string $filename): bool {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, self::get_supported_types());
    }
}
