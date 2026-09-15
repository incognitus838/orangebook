<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

namespace Database\Factories;

use App\Models\Book;
//use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition()
    {
	    return [
	        'category_id'   => rand(1,10),
	        'book_code'     => rand(1100000, 8899999),
	        'author'        => $this->faker->randomElement(['Chinua Achebe', 'Wole Soyinka', 'Chimamanda Ngozi Adichie', 'Cyprian Ekwensi', 'Buchi Emecheta', 'Helon Habila']),
	        'title'         => $this->faker->randomElement(['Things Fall Apart', 'The Lion and the Jewel', 'Purple Hibiscus', 'The Passport of Mallam Ilia', 'The Joys of Motherhood', 'Waiting for an Angel', 'New Oxford English SS2', 'New General Mathematics JSS2']),
	        'availability'  => rand(5, 25),
	        'isbn_number'   => $this->faker->ean13(),
	        'cover_image'   => 'https://placeit/200x300',
	    ];

	}
}