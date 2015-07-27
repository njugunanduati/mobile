<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    protected $table = 'nationality';
	// getting program description at program brochure tab
	public function fetchPrgDescription($nationality, $country) 
	{
	    $student_type=$this->getStudentType($nationality,$country);
	    $result = ProgramDetails::where('region_included', 'LIKE', '%'.$student_type.'%')->get();
	    return $result;
	}

	   // fetch Student type
	   public function getStudentType($nationality,$country) 
	   {
	   		$region = Nationality::where('Adjective','like',$nationality)->first();
	   		$student_type='Rest of the World';
	       if(!empty($region)){
	       		$region = json_decode($region,true);
	           	$region=$region['country_region'];
	       }else{
	           $region='';
	       }
	       if(strcasecmp($nationality,'Indian')==0){
	           if(strcasecmp($country,'India')==0){
	               $student_type='Indian Nationals';
	           }else{
	               $student_type='Indian Overseas';
	           }
	       }else{
	           if(strcasecmp($region,'Africa')==0) {
	               $student_type='Africa';
	           }elseif(strcasecmp($region,'South East Asia')==0) {
	               $student_type='South East Asia';
	           }elseif(strcasecmp($region,'West / Western Asia')==0) {
	               $student_type='West / Western Asia';
	           }elseif(strcasecmp($region,'SAARC')==0) {
	               $student_type='SAARC';
	           }else{
	               $student_type='Rest of the World';
	           }
	       }
	       return $student_type;
	   }
}
