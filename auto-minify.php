<?php
/**
 * Auto-Minifier for Malisafi MLS
 * 
 * Automatically creates minified versions of CSS and JS files
 * 
 * Run: php auto-minify.php
 */

class MalisafiMinifier {
    
    private $css_dir;
    private $js_dir;
    private $stats = [
        'css_minified' => 0,
        'js_minified' => 0,
        'bytes_saved' => 0
    ];
    
    public function __construct() {
        $this->css_dir = __DIR__ . '/assets/css/';
        $this->js_dir = __DIR__ . '/assets/js/';
    }
    
    public function run() {
        echo "🎯 Malisafi MLS - Auto Minifier\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $this->minifyCSS();
        $this->minifyJS();
        $this->showStats();
    }
    
    /**
     * Minify CSS files
     */
    private function minifyCSS() {
        echo "📄 Minifying CSS Files...\n";
        
        $files = glob($this->css_dir . '*.css');
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Skip if already minified
            if (strpos($filename, '.min.css') !== false) {
                continue;
            }
            
            $content = file_get_contents($file);
            $original_size = strlen($content);
            
            // Minify
            $minified = $this->minifyCSSContent($content);
            $minified_size = strlen($minified);
            
            // Save minified version
            $min_file = str_replace('.css', '.min.css', $file);
            file_put_contents($min_file, $minified);
            
            $saved = $original_size - $minified_size;
            $percent = round(($saved / $original_size) * 100, 1);
            
            $this->stats['css_minified']++;
            $this->stats['bytes_saved'] += $saved;
            
            echo "  ✓ " . basename($file) . " → " . basename($min_file) . 
                 " (-{$percent}%)\n";
        }
        
        echo "\n";
    }
    
    /**
     * Minify JavaScript files
     */
    private function minifyJS() {
        echo "📜 Minifying JavaScript Files...\n";
        
        $files = glob($this->js_dir . '*.js');
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Skip if already minified
            if (strpos($filename, '.min.js') !== false) {
                continue;
            }
            
            $content = file_get_contents($file);
            $original_size = strlen($content);
            
            // Skip empty files
            if ($original_size == 0) {
                echo "  ⚠️  " . basename($file) . " is empty, skipping\n";
                continue;
            }
            
            // Minify
            $minified = $this->minifyJSContent($content);
            $minified_size = strlen($minified);
            
            // Save minified version
            $min_file = str_replace('.js', '.min.js', $file);
            file_put_contents($min_file, $minified);
            
            $saved = $original_size - $minified_size;
            $percent = $original_size > 0 ? round(($saved / $original_size) * 100, 1) : 0;
            
            $this->stats['js_minified']++;
            $this->stats['bytes_saved'] += $saved;
            
            echo "  ✓ " . basename($file) . " → " . basename($min_file) . 
                 " (-{$percent}%)\n";
        }
        
        echo "\n";
    }
    
    /**
     * Minify CSS content
     */
    private function minifyCSSContent($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove spaces around certain characters
        $css = str_replace([' {', '{ ', ' }', '} ', ' :', ': ', ' ;', '; ', ' ,', ', '], 
                          ['{', '{', '}', '}', ':', ':', ';', ';', ',', ','], $css);
        
        // Remove last semicolon in blocks
        $css = preg_replace('/;}/','}',$css);
        
        return trim($css);
    }
    
    /**
     * Minify JavaScript content
     */
    private function minifyJSContent($js) {
        // Remove single-line comments (but preserve URLs)
        $js = preg_replace('~//[^\n]*~', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('~/\*.*?\*/~s', '', $js);
        
        // Remove console.log statements
        $js = preg_replace('/console\.(log|debug|info|warn|error)\s*\([^)]*\)\s*;?/', '', $js);
        
        // Remove whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove spaces around operators (carefully)
        $js = preg_replace('/\s*([{}();\[\]=,:<>!&|+-])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Show statistics
     */
    private function showStats() {
        echo str_repeat("=", 60) . "\n";
        echo "📊 MINIFICATION SUMMARY\n";
        echo str_repeat("=", 60) . "\n\n";
        
        echo "Files Minified:\n";
        echo "  CSS: {$this->stats['css_minified']} files\n";
        echo "  JS: {$this->stats['js_minified']} files\n";
        echo "\n";
        
        echo "Total Savings: " . $this->formatBytes($this->stats['bytes_saved']) . "\n";
        
        echo "\n✅ Minification Complete!\n";
        echo "\n💡 Next Steps:\n";
        echo "  1. Update enqueue scripts to use .min.css/.min.js in production\n";
        echo "  2. Test minified files for functionality\n";
        echo "  3. Enable GZIP compression on server\n";
    }
    
    /**
     * Format bytes
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// Run the minifier
$minifier = new MalisafiMinifier();
$minifier->run();
