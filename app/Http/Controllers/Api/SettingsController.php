<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
        $this->middleware('permission:manage-settings');
    }

    /**
     * Get all theme settings
     */
    public function getThemeSettings(): JsonResponse
    {
        $settings = Setting::getThemeSettings();
        
        return response()->json([
            'theme_settings' => $settings,
        ]);
    }

    /**
     * Update theme settings
     */
    public function updateThemeSettings(Request $request): JsonResponse
    {
        $request->validate([
            'background_color' => 'string|nullable',
            'surface_color' => 'string|nullable',
            'text_color' => 'string|nullable',
            'primary_action_color' => 'string|nullable',
            'secondary_action_color' => 'string|nullable',
            'font_family' => 'string|nullable',
        ]);

        $themeSettings = [
            'background_color' => $request->input('background_color'),
            'surface_color' => $request->input('surface_color'),
            'text_color' => $request->input('text_color'),
            'primary_action_color' => $request->input('primary_action_color'),
            'secondary_action_color' => $request->input('secondary_action_color'),
            'font_family' => $request->input('font_family'),
        ];

        foreach ($themeSettings as $key => $value) {
            if ($value !== null) {
                Setting::set($key, $value, 'string');
            }
        }

        $this->logger->log('تحديث إعدادات المظهر', 'تم تحديث إعدادات المظهر');

        return response()->json([
            'message' => 'Theme settings updated successfully',
            'theme_settings' => Setting::getThemeSettings(),
        ]);
    }

    /**
     * Get company settings for the authenticated admin
     */
    public function getCompanySettings(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin') && !$user->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $settings = [
            'companyName' => $user->company_name,
            'companyPhone' => $user->company_phone,
            'companyPhone2' => $user->phone2,
            'companyEmail' => $user->email,
            'companyAddress' => $user->company_address,
            'currency' => $user->getCompanyCurrency(),
            'invoice_number_prefix' => $user->getInvoiceNumberPrefix(),
            'current_invoice_number' => $user->getCurrentInvoiceNumber(),
        ];

        return response()->json(['company_settings' => $settings]);
    }

    /**
     * Update company settings for the authenticated admin
     */
    public function updateCompanySettings(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin') && !$user->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'company_name' => 'string|nullable|max:255',
            'company_phone' => 'string|nullable|max:20',
            'company_phone2' => 'string|nullable|max:20',
            'company_email' => 'email|nullable|max:255',
            'company_address' => 'string|nullable|max:500',
            'company_currency' => 'string|nullable|max:10',
            'invoice_number_prefix' => 'string|nullable|max:10',
            'current_invoice_number' => 'integer|nullable|min:1',
        ]);

        $user->update([
            'company_name' => $request->input('company_name'),
            'company_phone' => $request->input('company_phone'),
            'phone2' => $request->input('company_phone2'),
            'email' => $request->input('company_email'),
            'company_address' => $request->input('company_address'),
            'company_currency' => $request->input('company_currency'),
            'invoice_number_prefix' => $request->input('invoice_number_prefix'),
            'current_invoice_number' => $request->input('current_invoice_number'),
        ]);

        $this->logger->log('تحديث إعدادات الشركة', 'تم تحديث إعدادات الشركة');

        return response()->json([
            'message' => 'Company settings updated successfully',
            'company_settings' => [
                'companyName' => $user->company_name,
                'companyPhone' => $user->company_phone,
                'companyPhone2' => $user->phone2,
                'companyEmail' => $user->email,
                'companyAddress' => $user->company_address,
                'currency' => $user->getCompanyCurrency(),
                'invoice_number_prefix' => $user->getInvoiceNumberPrefix(),
                'current_invoice_number' => $user->getCurrentInvoiceNumber(),
            ],
        ]);
    }

    /**
     * Get all settings (theme + company)
     */
    public function getAllSettings(): JsonResponse
    {
        $user = Auth::user();
        
        $settings = [
            'theme_settings' => Setting::getThemeSettings(),
            'user_permissions' => \App\Http\Middleware\PermissionMiddleware::getUserPermissions(),
        ];

        // Add company settings if user is admin
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            $settings['company_settings'] = [
                'companyName' => $user->company_name,
                'companyPhone' => $user->company_phone,
                'companyPhone2' => $user->phone2,
                'companyEmail' => $user->email,
                'companyAddress' => $user->company_address,
                'currency' => $user->getCompanyCurrency(),
                'invoice_number_prefix' => $user->getInvoiceNumberPrefix(),
                'current_invoice_number' => $user->getCurrentInvoiceNumber(),
            ];
        }

        return response()->json($settings);
    }

    /**
     * Reset theme settings to defaults
     */
    public function resetThemeSettings(): JsonResponse
    {
        $defaultSettings = [
            'background_color' => '#08081a',
            'surface_color' => '#1a1a2e',
            'text_color' => '#e0e0e0',
            'primary_action_color' => '#1a56db',
            'secondary_action_color' => '#0ea5e9',
            'font_family' => 'Noto Kufi Arabic',
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::set($key, $value, 'string');
        }

        $this->logger->log('إعادة تعيين إعدادات المظهر', 'تم إعادة تعيين إعدادات المظهر إلى الافتراضية');

        return response()->json([
            'message' => 'Theme settings reset to defaults',
            'theme_settings' => Setting::getThemeSettings(),
        ]);
    }
}
