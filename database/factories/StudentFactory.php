<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'student_number' => $this->faker->unique()->numerify('2026-#####'),
            'first_name' => $this->faker->firstName(),
            'middle_name' => $this->faker->optional()->firstName(),
            'last_name' => $this->faker->lastName(),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'year_level' => $this->faker->randomElement(['7', '8', '9', '10', '11', '12']),
            'section' => $this->faker->bothify('Section-#?'),
            'status' => 'active',
        ];
    }
}
