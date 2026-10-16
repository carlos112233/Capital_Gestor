<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AppSetting::updateOrCreate(
            ['id' => 1],
            [
                'app_name' => AppSetting::DEFAULT_APP_NAME,
                'logo_path' => null,
                'favicon_path' => null,
                'primary_color' => AppSetting::DEFAULT_PRIMARY_COLOR,
                'secondary_color' => AppSetting::DEFAULT_SECONDARY_COLOR,
                'sidebar_color' => AppSetting::DEFAULT_SIDEBAR_COLOR,
                'font_family' => AppSetting::DEFAULT_FONT_FAMILY,
                'theme_mode' => AppSetting::DEFAULT_THEME_MODE,
            ]
        );

        AppSetting::clearSettingsCache();
    }
}

