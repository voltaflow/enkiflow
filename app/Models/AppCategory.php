<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'app_name',
        'category',
        'productivity_level',
    ];

    /**
     * Get the application sessions for this app.
     */
    public function applicationSessions(): HasMany
    {
        return $this->hasMany(ApplicationSession::class, 'app_name', 'app_name');
    }

    /**
     * Get or create a category for an app.
     */
    public static function getOrCreateForApp(string $appName): self
    {
        return self::firstOrCreate(
            ['app_name' => $appName],
            [
                'category' => self::guessCategory($appName),
                'productivity_level' => self::guessProductivityLevel($appName),
            ]
        );
    }

    /**
     * Guess the category based on app name.
     */
    protected static function guessCategory(string $appName): string
    {
        $appNameLower = strtolower($appName);

        // Development tools
        if (preg_match('/(code|studio|developer|terminal|console|git|docker|postman)/i', $appName)) {
            return 'Development';
        }

        // Communication
        if (preg_match('/(slack|teams|zoom|discord|skype|mail|outlook|gmail)/i', $appName)) {
            return 'Communication';
        }

        // Browsers
        if (preg_match('/(chrome|firefox|safari|edge|browser)/i', $appName)) {
            return 'Web Browsing';
        }

        // Productivity
        if (preg_match('/(excel|word|powerpoint|notion|evernote|todoist|trello)/i', $appName)) {
            return 'Productivity';
        }

        // Design
        if (preg_match('/(photoshop|illustrator|figma|sketch|canva)/i', $appName)) {
            return 'Design';
        }

        // Entertainment
        if (preg_match('/(spotify|netflix|youtube|twitch|steam|game)/i', $appName)) {
            return 'Entertainment';
        }

        return 'Other';
    }

    /**
     * Guess the productivity level based on app name and category.
     */
    protected static function guessProductivityLevel(string $appName): string
    {
        $category = self::guessCategory($appName);

        // Productive categories
        if (in_array($category, ['Development', 'Design', 'Productivity'])) {
            return 'productive';
        }

        // Distracting categories
        if (in_array($category, ['Entertainment'])) {
            return 'distracting';
        }

        // Neutral for everything else
        return 'neutral';
    }

    /**
     * Update productivity level for multiple apps.
     */
    public static function updateProductivityLevels(array $appLevels): void
    {
        foreach ($appLevels as $appName => $level) {
            self::where('app_name', $appName)->update(['productivity_level' => $level]);
        }
    }

    /**
     * Get productivity stats for all apps.
     */
    public static function getProductivityStats(): array
    {
        return self::selectRaw('productivity_level, COUNT(*) as count')
            ->groupBy('productivity_level')
            ->pluck('count', 'productivity_level')
            ->toArray();
    }
}
