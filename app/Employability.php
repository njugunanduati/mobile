<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employability extends Model
{
    protected $table = 'employment_details';
	protected $fillable = array('work_experience_year','work_experience_month','company_name','company_address',
                                'designation','functional_area','from_date','to_date', 'job_responsibility');
}
