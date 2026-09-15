<?php

namespace App\Support;

class NigerianNames
{
    public const FIRST_MALE = [
        'Chinedu', 'Emeka', 'Ibrahim', 'Tunde', 'Obinna', 'Kunle', 'Ahmed', 'Chukwuemeka',
        'Segun', 'Yusuf', 'Nnamdi', 'Femi', 'Sani', 'Ifeanyi', 'Bola', 'Kayode', 'Uche',
        'Musa', 'Tope', 'Chibueze',
    ];

    public const FIRST_FEMALE = [
        'Adaeze', 'Chioma', 'Amina', 'Funke', 'Ngozi', 'Yetunde', 'Halima', 'Ifeoma',
        'Bukola', 'Zainab', 'Amaka', 'Tosin', 'Fatima', 'Chiamaka', 'Kemi', 'Blessing',
        'Nkechi', 'Hadiza', 'Oluwaseun', 'Mercy',
    ];

    public const LAST = [
        'Okonkwo', 'Adeyemi', 'Okafor', 'Balogun', 'Mohammed', 'Nwosu', 'Olawale', 'Abdullahi',
        'Eze', 'Adebayo', 'Ibrahim', 'Okoro', 'Lawal', 'Chukwu', 'Suleiman', 'Ogunleye',
        'Bello', 'Nwachukwu', 'Danjuma', 'Afolabi',
    ];

    public const CITIES = [
        'Ikeja', 'Surulere', 'Yaba', 'Agege', 'Ikorodu', 'Abeokuta', 'Ibadan', 'Enugu', 'Onitsha', 'Kaduna',
    ];

    public const TONGUES = ['Yoruba', 'Igbo', 'Hausa', 'English', 'Edo', 'Ibibio'];

    public static function first(string $gender = 'male'): string
    {
        $pool = $gender === 'female' ? self::FIRST_FEMALE : self::FIRST_MALE;
        return $pool[array_rand($pool)];
    }

    public static function last(): string
    {
        return self::LAST[array_rand(self::LAST)];
    }

    public static function full(string $gender = 'male'): string
    {
        return self::first($gender) . ' ' . self::last();
    }

    public static function city(): string
    {
        return self::CITIES[array_rand(self::CITIES)];
    }

    public static function address(): string
    {
        $streets = ['Allen Avenue', 'Awolowo Road', 'Herbert Macaulay Way', 'Adeniran Ogunsanya', 'Broad Street', 'Ahmadu Bello Way'];
        return rand(1, 99) . ' ' . $streets[array_rand($streets)] . ', ' . self::city() . ', Lagos, Nigeria';
    }

    public static function phone(): string
    {
        $prefix = ['0803', '0805', '0806', '0807', '0809', '0810', '0813', '0814', '0816'];
        return $prefix[array_rand($prefix)] . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
    }
}
