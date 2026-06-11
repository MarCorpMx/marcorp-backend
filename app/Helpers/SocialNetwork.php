<?php

namespace App\Helpers;

class SocialNetwork
{
    public static function all(): array
    {
        return [

            'facebook' => [
                'label' => 'Facebook',
                'icon'  => 'https://cdn-icons-png.flaticon.com/24/733/733547.png',
            ],

            'instagram' => [
                'label' => 'Instagram',
                'icon'  => 'https://cdn-icons-png.flaticon.com/24/733/733558.png',
            ],

            'tiktok' => [
                'label' => 'TikTok',
                'icon'  => 'https://cdn-icons-png.flaticon.com/24/3046/3046121.png',
            ],

            'youtube' => [
                'label' => 'YouTube',
                'icon'  => 'https://cdn-icons-png.flaticon.com/24/1384/1384060.png',
            ],

            'x' => [
                'label' => 'X',
                'icon'  => 'https://cdn-icons-png.flaticon.com/24/5969/5969020.png',
            ],
        ];
    }
}
