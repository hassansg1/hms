<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $dates = ['created_at', 'dob','updated_at', 'join_date','probition_from','probition_to','next_review_date'];
    protected $fillable = ['user_id', 'first_name', 'last_name', 'sex', 'dob', 'join_date', 'desg', 'department_id', 'salary',
        'photo','contact_next_of_kim','job_type','blood_group','contact_no','email_office_use',
        'job_nature','basic_salary','housing_salary','transportaion_allowance'];
    public function user() {
        return $this->belongsTo('App\User');
    }

    public function department() {
        // return $this->hasOne('App\Department');
        return $this->belongsTo('App\Department');
    }

    public function attendance() {
        return $this->hasMany('App\Attendance');
    }

    public function leave() {
        return $this->hasMany('App\Leave');
    }

    public function expense() {
        return $this->hasMany('App\Expense');
    }
}
