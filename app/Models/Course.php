<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Course extends Model { use SoftDeletes; protected $fillable=['name','code','status']; public function students(){return $this->hasMany(Student::class);} }
