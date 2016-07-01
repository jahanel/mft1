
heroku buildpacks:set heroku/php

<?php
// Code to randomly generate conjoint profiles to send to a Qualtrics instance

// Terminology clarification: 
// Task = Set of choices presented to respondent in a single screen (i.e. pair of candidates)
// Profile = Single list of attributes in a given task (i.e. candidate)
// Attribute = Category characterized by a set of levels (i.e. education level)
// Level = Value that an attribute can take in a particular choice task (i.e. "no formal education")

// Attributes and Levels stored in a 2-dimensional Array 

// Function to generate weighted random numbers
function weighted_randomize($prob_array, $at_key)
{
	$prob_list = $prob_array[$at_key];
	
	// Create an array containing cutpoints for randomization
	$cumul_prob = array();
	$cumulative = 0.0;
	for ($i=0; $i<count($prob_list); $i++){
		$cumul_prob[$i] = $cumulative;
		$cumulative = $cumulative + floatval($prob_list[$i]);
	}

	// Generate a uniform random floating point value between 0.0 and 1.0
	$unif_rand = mt_rand() / mt_getrandmax();

	// Figure out which integer should be returned
	$outInt = 0;
	for ($k = 0; $k < count($cumul_prob); $k++){
		if ($cumul_prob[$k] <= $unif_rand){
			$outInt = $k + 1;
		}
	}

	return($outInt);

}




$featurearray = array("Party" => array("Republican","Democrat"),"Marital Status" => array("Married","Single","Remarried","Currently Divorsed"),"Military Service" => array("None","U.S. Army","U.S. Marine Corps","U.S. Navy","U.S. Air Force","U.S. Coast Guard"),"Number of Children" => array("0","1","2","3","4"),"Political Experience" => array("None","Member of Town Council","Member of County Board","Mayor","Member of School Board","Sheriff"),"Profession" => array("Real Estate Attorney","Family Law Attorney","Corporate Attorney","Empolyment Law Attorney","Public Defender","High School Science Teacher","High School Math Teacher","High School English Teacher","High School Social Studies Teacher","Grade School Teacher","General Practitioner Physician","General Surgeon","Cardiologist","Radiologist","Dentist","Pediatrician","Tax Accountant","Corporate Accountant","Insurance Agent","Personal Banker","Loan Officer","Financial Advisor","Owener and Operator of Local Bakery","Owner and Operator of Local Clothing Store","Owner and Operator of Local Hardware Store","Television Reporter","Social Worker","Dairy Farmer","Farmer","Poultry Farmer","Pig Farmer","Community Organizer","Television News Anchor","Operator of Local Coffee Shop","Owner and Operator of Local Restaurant","Anesthesiologist","High School Principal","Software Developer",": Owner and Operator of Contracting Firm"),"Why They Are Running for Office?" => array("I believe in compassion. I am running for office to care for those with the most need. That means helping and protecting the neediest in our community. "," I believe in respecting the men and women that built this community years ago. I am running for office to honor their sacrifices. We need to look back to the advice these past leaders. ","I am running for office to make sure everyone in the community gets a fair and equal shot at success. I believe giving everyone in the community access to resources is the right thing to do. That is only fair. "," I am running for office because I am a proud American. I believe it is our patriotic duty to serve this community. Together as Americans we can make the community stronger. "," I am running for office because I wish to restore values in this community. I believe corruption has tainted our communities, and wish to make the country more wholesome and clean."));

$restrictionarray = array();

// Indicator for whether weighted randomization should be enabled or not
$weighted = 0;

// K = Number of tasks displayed to the respondent
$K = 5;

// N = Number of profiles displayed in each task
$N = 2;

// num_attributes = Number of Attributes in the Array
$num_attributes = count($featurearray);


$featureArrayNew = $featurearray;


// Initialize the array returned to the user
// Naming Convention
// Level Name: F-[task number]-[profile number]-[attribute number]
// Attribute Name: F-[task number]-[attribute number]
// Example: F-1-3-2, Returns the level corresponding to Task 1, Profile 3, Attribute 2 
// F-3-3, Returns the attribute name corresponding to Task 3, Attribute 3

$returnarray = array();

// For each task $p
for($p = 1; $p <= $K; $p++){

	// For each profile $i
	for($i = 1; $i <= $N; $i++){

		// Repeat until non-restricted profile generated
		$complete = False;

		while ($complete == False){

			// Create a count for $attributes to be incremented in the next loop
			$attr = 0;
			
			// Create a dictionary to hold profile's attributes
			$profile_dict = array();

			// For each attribute $attribute and level array $levels in task $p
			foreach($featureArrayNew as $attribute => $levels){	
				
				// Increment attribute count
				$attr = $attr + 1;

				// Create key for attribute name
				$attr_key = "F-" . (string)$p . "-" . (string)$attr;

				// Store attribute name in $returnarray
				$returnarray[$attr_key] = $attribute;

				// Get length of $levels array
				$num_levels = count($levels);

				// Randomly select one of the level indices
				if ($weighted == 1){
					$level_index = weighted_randomize($probabilityarray, $attribute) - 1;

				}else{
					$level_index = mt_rand(1,$num_levels) - 1;	
				}	

				// Pull out the selected level
				$chosen_level = $levels[$level_index];
			
				// Store selected level in $profileDict
				$profile_dict[$attribute] = $chosen_level;

				// Create key for level in $returnarray
				$level_key = "F-" . (string)$p . "-" . (string)$i . "-" . (string)$attr;

				// Store selected level in $returnarray
				$returnarray[$level_key] = $chosen_level;

			}

			$clear = True;
			// Cycle through restrictions to confirm/reject profile
			if(count($restrictionarray) != 0){

				foreach($restrictionarray as $restriction){
					$false = 1;
					foreach($restriction as $pair){
						if ($profile_dict[$pair[0]] == $pair[1]){
							$false = $false*1;
						}else{
							$false = $false*0;
						}
						
					}
					if ($false == 1){
						$clear = False;
					}
				}
			}
			$complete = $clear;
		}
	}


}

// Return the array back to Qualtrics
print  json_encode($returnarray);
?>
