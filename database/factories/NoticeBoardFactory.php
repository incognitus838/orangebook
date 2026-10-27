<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BackgroundImage;
use App\Models\NoticeBoard;

class NoticeBoardFactory extends Factory
{
    protected $model = NoticeBoard::class;

    public function definition()
    {
        $notices = [
            ['PTA meeting', 'Parents are invited to the PTA meeting in the school hall at 10:00am on Saturday. Please come with your ward’s report folder.'],
            ['First Term exams', 'First Term examinations begin on Monday. Students should arrive by 7:30am with their writing materials. No phones in the hall.'],
            ['Inter-house sports', 'Ikeja Secondary School inter-house sports holds this Friday on the school field. House captains should submit names today.'],
            ['School fees reminder', 'Second-term fees should be paid into the school account before resumption. See the bursar for the account details.'],
            ['Cultural day', 'Cultural Day is next week Thursday. Students may wear traditional attire. Yoruba, Igbo and Hausa performances start at 11am.'],
            ['WAEC registration', 'SS3 students must complete WAEC registration with the exams officer this week. Bring two passport photographs.'],
        ];
        $pick = $notices[array_rand($notices)];

        return [
            'background_id' => BackgroundImage::inRandomOrder()->value('id'),
            'title' => $pick[0],
            'publish_date' => $this->faker->dateTimeBetween('-10 days', 'now'),
            'expire_date' => $this->faker->dateTimeBetween('now', '+20 days'),
            'description' => $pick[1],
            'attachment_file' => null,
        ];
    }
}
