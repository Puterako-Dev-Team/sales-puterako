<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SurveyColumnFormula extends Model
{
    protected $fillable = [
        'column_key',
        'formula',
        'dependencies',
        'description',
        'group_name',
        'is_active',
        'order',
    ];

    protected $casts = [
        'dependencies' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope to get only active formulas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by execution order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get all active formulas as a collection keyed by column_key.
     */
    public static function getActiveFormulas(): Collection
    {
        return static::active()
            ->ordered()
            ->get()
            ->keyBy('column_key');
    }

    /**
     * Get formula configuration for JavaScript.
     * Returns array format suitable for frontend use.
     */
    public static function getFormulaConfig(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->map(function ($formula) {
                return [
                    'column_key' => $formula->column_key,
                    'formula' => $formula->formula,
                    'dependencies' => $formula->dependencies,
                    'description' => $formula->description,
                    'group_name' => $formula->group_name,
                ];
            })
            ->toArray();
    }

    /**
     * Parse the formula string and extract column dependencies.
     * Automatically updates the dependencies field based on formula.
     */
    public function parseAndSetDependencies(): void
    {
        $formula = $this->formula;
        
        // Extract all variable names (non-numeric identifiers not being function names)
        // Matches identifiers like: horizon, vertical, up_08, etc.
        preg_match_all('/\b([a-z][a-z0-9_]*)\b/i', $formula, $matches);
        
        $identifiers = $matches[1] ?? [];
        
        // Filter out common math functions and keywords
        $excludedKeywords = ['Math', 'abs', 'ceil', 'floor', 'round', 'max', 'min', 'pow', 'sqrt', 'if', 'else', 'true', 'false', 'null', 'PI', 'E'];
        
        $dependencies = array_values(array_unique(array_filter($identifiers, function ($id) use ($excludedKeywords) {
            return !in_array($id, $excludedKeywords);
        })));
        
        $this->dependencies = $dependencies;
    }

    /**
     * Boot method to auto-parse dependencies before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->parseAndSetDependencies();
        });
    }

    /**
     * Validate formula syntax.
     * Returns true if valid, or error message if invalid.
     */
    public function validateFormula(): bool|string
    {
        $formula = $this->formula;
        
        // Check for balanced parentheses
        $open = substr_count($formula, '(');
        $close = substr_count($formula, ')');
        if ($open !== $close) {
            return 'Unbalanced parentheses in formula';
        }
        
        // Check for dangerous patterns (security)
        $dangerousPatterns = [
            '/eval\s*\(/i',
            '/function\s*\(/i',
            '/new\s+/i',
            '/\[\s*\]/i',
            '/\{\s*\}/i',
            '/require|import|export/i',
            '/document|window|global/i',
            '/fetch|XMLHttpRequest/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $formula)) {
                return 'Formula contains potentially dangerous code';
            }
        }
        
        // Basic syntax check - try parsing with JavaScript-like grammar
        // This is a simple check, not exhaustive
        if (preg_match('/[^a-z0-9_\s\+\-\*\/\(\)\.\,\>\<\=\!\?\:]/i', $formula)) {
            // Contains unusual characters, might be invalid
            // But allow common math operators and ternary
        }
        
        return true;
    }

    /**
     * Get sample default formulas for initial setup.
     */
    public static function getDefaultFormulas(): array
    {
        return [
            [
                'column_key' => 'up_08',
                'formula' => '(horizon + vertical) / 0.8',
                'description' => 'UP 0.8 = (Horizon + Vertical) / 0.8',
                'group_name' => 'Dimensi',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'column_key' => 'utp',
                'formula' => 'up_08',
                'description' => 'UTP = UP 0.8 (same length as UP cable)',
                'group_name' => 'Kabel',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'column_key' => 'awg_22',
                'formula' => 'up_08',
                'description' => 'AWG 22 = UP 0.8',
                'group_name' => 'Kabel',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'column_key' => 'nyy_3_x_1_5',
                'formula' => 'up_08',
                'description' => 'NYY 3 X 1,5 = UP 0.8',
                'group_name' => 'Kabel',
                'is_active' => true,
                'order' => 4,
            ],
            [
                'column_key' => 'fo',
                'formula' => 'up_08',
                'description' => 'FO = UP 0.8',
                'group_name' => 'Kabel',
                'is_active' => true,
                'order' => 5,
            ],
        ];
    }
}
