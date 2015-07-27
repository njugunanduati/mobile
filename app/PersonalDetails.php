<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PersonalDetails extends Model
{
    protected $table = 'personal_details';
	protected $fillable = array("first_name","last_name","middle_name","dob","gender","nationality","employee",
		"father_name","mother_name",'native_language','birth_country','birth_place','marital_status','religion',
		'hadicapped','handicapped_details','spouse_name','permanent_address1','permanent_address2', 
		'permanent_city','permanent_state','permanent_pin','permanent_country','permanent_contactno',
		'communication_address1','communication_address2','communication_city','communication_state',
		'communication_pin','communication_country','communication_contactno','emergency_contact','authorized');
}
