<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Academics extends Model
{
    protected $table = 'academic_details';
	protected $fillable=array('school_name_x','place_x','country_x','state_x','passing_year_x','board_x',
                                  'marks_x','percentage_x','school_name_xii','place_xii','country_xii',
                                  'state_xii','passing_year_xii','board_xii','marks_xii',    'percentage_xii',
                                  'stream_xii','diploma','diploma_stream','diploma_board','diploma_institute',
                                  'diploma_country',    'diploma_city','diploma_percentage','actual_duration',
                                  'diploma_start_year','diploma_completed_year','pre_requisites_status',
                                  'provisional_admission','term_condition');
	}
}
