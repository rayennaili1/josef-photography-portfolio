<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        
        $defaults = [
            // Branding & Labels
            'site_title' => 'Josef Nhidi Photography',
            'site_tagline' => 'Professional Portrait & Event Photographer',
            'logo_text' => 'JOSEF NHIDI',
            'primary_color' => '#050505',
            'accent_color' => '#d4af37',
            'bg_color' => '#ffffff',
            'portraits_label' => 'PORTRAITS',
            'events_label' => 'EVENTS',
            'footer_copy' => '© ' . date('Y') . ' Josef Nhidi Photography. All Rights Reserved.',
            
            // Global System
            'gallery_bg_text' => 'GALLERY',
            'gallery_title' => 'Selected Work',
            'gallery_tagline' => 'PORTFOLIO',
            'allow_right_click' => 'false',
            'show_about_artist' => 'true',
            
            // SEO & Discoverability
            'seo_description' => 'Professional photography portfolio of Josef Nhidi (Youssef Nhidi), specializing in high-end portraits and event coverage.',
            'seo_keywords' => 'photography, portraits, events, josef nhidi, youssef nhidi',
            'seo_author' => 'Josef Nhidi',
            'og_image_url' => '',
            'google_verification_tag' => '',
        ];

        return response()->json(array_merge($defaults, $settings->toArray()));
    }

    public function update(Request $request)
    {
        $settings = $request->all();
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return response()->json(['message' => 'Settings updated successfully']);
    }
}
