<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SelectProgram extends Model
{
    protected $table = 'select_program';
	protected $fillable = array('program_name','nationality','country',
		'dual_specialization','agree','exam_mode');
	
	public static function checkProgramForUser($regId)
	{
		$program = SelectProgram::where('registration_id','=',$regId)->first();
		return $program;
	}

}
