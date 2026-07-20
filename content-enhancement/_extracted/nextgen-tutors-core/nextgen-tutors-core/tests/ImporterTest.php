<?php
use PHPUnit\Framework\TestCase;

class ImporterTest extends TestCase {

    public function test_format_detection() {
        $importer = NGT_Importer::get_instance();
        
        $this->assertEquals('csv', $importer->detect_format('data.csv'));
        $this->assertEquals('json', $importer->detect_format('data.JSON'));
        $this->assertEquals('unknown', $importer->detect_format('data.txt'));
    }

    public function test_json_parsing() {
        $importer = NGT_Importer::get_instance();
        $temp_file = sys_get_temp_dir() . '/test.json';
        
        $data = [['name' => 'John'], ['name' => 'Jane']];
        file_put_contents($temp_file, json_encode($data));
        
        $parsed = $importer->parse_file($temp_file);
        $this->assertCount(2, $parsed);
        $this->assertEquals('John', $parsed[0]['name']);
        
        unlink($temp_file);
    }
}
