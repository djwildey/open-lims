<?php
/**
 * @package sample
 * @version 0.4.0.0
 * @author Roman Konertz
 * @copyright (c) 2008-2010 by Roman Konertz
 * @license GPLv3
 * 
 * This file is part of Open-LIMS
 * Available at http://www.open-lims.org
 * 
 * This program is free software;
 * you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation;
 * version 3 of the License.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 
 */
require_once("interfaces/sample_item.interface.php");

if (constant("UNIT_TEST") == false or !defined("UNIT_TEST"))
{
	require_once("access/sample_has_item.access.php");
}

/**
 * Sample Item Management Class
 * @package sample
 */
class SampleItem implements SampleItemInterface, EventListenerInterface
{
	private $sample_id;
	private $item_id;
	
	private $gid;
	
	private $item_class_id;

	/**
	 * @param integer $sample_id
	 */
    function __construct($sample_id)
    {
    	if ($sample_id == null)
    	{
    		$this->sample_id = null;
    	}
    	else
    	{
    		$this->sample_id = $sample_id;
    		$this->sample = new Sample($sample_id);
    	}
    }
    
    function __destruct()
    {
    	unset($this->sample_id);
    	unset($this->item_id);
    	unset($this->gid);
    	unset($this->item_class_id);
    }
    
    /**
     * Links an item to the sample
     * @return bool
     */
    public function link_item()
    {
    	global $transaction;
    	
    	if ($this->item_id and $this->sample_id)
    	{
    		$transaction_id = $transaction->begin();
    		
    		$sample_has_item = new SampleHasItem_Access(null);
    		
    		if (is_numeric($this->gid))
	    	{
	    		$primary_key = $sample_has_item->create($this->sample_id, $this->item_id, $this->gid);
	    	}
	    	else
	    	{
	    		$primary_key = $sample_has_item->create($this->sample_id, $this->item_id, null);
	    	}
	    	
    		if ($primary_key != null)
    		{ 	    		
    			if ($transaction_id != null)
    			{
					$transaction->commit($transaction_id);
				}
    			return true;
    		}
    		else
    		{
    			if ($transaction_id != null)
    			{
					$transaction->rollback($transaction_id);
				}
    			return false;
    		}
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * Unlinks an item from a specific sample
     * @return bool
     */
    public function unlink_item()
    {
    	if ($this->item_id and $this->sample_id)
    	{
    		$primary_key = SampleHasItem_Access::get_entry_by_item_id_and_sample_id($this->item_id, $this->sample_id);
    		$sample_has_item = new SampleHasItem_Access($primary_key);
    		
    		if ($sample_has_item->delete())
    		{
  				// Event
	  			$item_unlink_event = new ItemUnlinkEvent($this->item_id);
				$event_handler = new EventHandler($item_unlink_event);
					
				if ($event_handler->get_success() == false)
				{
					if ($transaction_id != null)
					{
						$transaction->rollback($transaction_id);
					}
					return false;
				}
    			
    			return true;
    		}
    		else
    		{
    			return false;
    		}
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * Unlinks an item from all samples
     * @return bool
     */
    public function unlink_item_full()
    {
    	
    	global $transaction;
    	
    	if ($this->item_id)
    	{
    		$transaction_id = $transaction->begin();
    		
  			$sample_has_item_pk_array = SampleHasItem_Access::list_entries_by_item_id($this->item_id);
  			  			
  			if (is_array($sample_has_item_pk_array))
  			{
  				if (count($sample_has_item_pk_array) >= 1)
  				{
	  				foreach ($sample_has_item_pk_array as $key => $value)
	  				{
	  					$sample_has_item = new SampleHasItem_Access($value);
	  					if ($sample_has_item->delete() == false)
	  					{
	  						if ($transaction_id != null)
	  						{
								$transaction->rollback($transaction_id);
							}
							return false;
	  					}
	  				}
	  				
	  				if ($transaction_id != null)
	  				{
						$transaction->commit($transaction_id);
					}
					return true;
  				}
  				else
  				{
  					return true;
  				}				
  			}
  			else
  			{
  				return false;
  			}
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * @return array
     */
    public function get_sample_items()
    {
    	if ($this->sample_id)
    	{
    		$sample_has_item_array = SampleHasItem_Access::list_entries_by_sample_id($this->sample_id);
    	
    		if (is_array($sample_has_item_array) and count($sample_has_item_array) >= 1)
    		{
    			$return_array = array();
    			
    			foreach($sample_has_item_array as $key => $value)
    			{
    				$sample_has_item = new SampleHasItem_Access($value);
    				array_push($return_array, $sample_has_item->get_item_id());
    			}
    			return $return_array;
    		}
    		else
    		{
    			return null;
    		}
    	}
    	else
    	{
    		return null;
    	}
    }
    
    /**
     * @param integer $item_id
     * @return bool
     */
    public function set_item_id($item_id)
    {
    	if (is_numeric($item_id))
    	{
    		$this->item_id = $item_id;
    		return true;
    	}else{
    		return false;
    	}
    }
    
    /**
     * @param integer $gid
     * @return bool
     */
    public function set_gid($gid)
    {
    	if (is_numeric($gid)) 
    	{
    		$this->gid = $gid;
    		return true;
    	}else{
    		return false;
    	}
    }
    
    /**
     * Checks if a class already exists
     * @param string $class_name
     * @return integer Class-ID
     */
    private function exist_class($class_name)
    {
    	if ($this->sample_id)
    	{
    		$item_array = $this->get_sample_items();
    		
    		if (is_array($item_array) and count($item_array) >= 1)
    		{
    			foreach($item_array as $key => $value)
    			{
    				$item_class_array = ItemClass::list_classes_by_item_id($value);
    				
    				if (is_array($item_class_array) and count($item_class_array) >= 1)
    				{
    					foreach($item_class_array as $item_key => $item_value)
    					{
    						$item_class = new ItemClass($item_value);
    						if (trim(strtolower($item_class->get_name())) == trim(strtolower($class_name)))
    						{
    							return $item_value;
    						}
    					}
    				}
    			}
    			return null;
    		}
    		else
    		{
    			return null;
    		}    		
    	}
    	else
    	{
    		return null;
    	}
    }
    
    /**
     * Adds the Item to a class
     * @param string $class_name
     * @return bool
     */
    public function set_class($class_name)
    {
    	global $user, $transaction;
    	
    	if ($this->item_id)
    	{
    		$transaction_id = $transaction->begin();
    	
	    	if (($item_class_id = $this->exist_class($class_name)) == null)
	    	{
	    		$item_class = new ItemClass(null);
	    		if (($item_class_id = $item_class->create($class_name, $user->get_user_id())) == null)
	    		{
	    			if ($transaction_id != null)
	    			{
						$transaction->rollback($transaction_id);
					}
					return false;
	    		}
	    	}
	    	else
	    	{
	    		$item_class = new ItemClass($item_class_id);
	    	}
	    	
	    	$this->item_class_id = $item_class_id;
	    	
	    	if ($item_class->link_item($this->item_id) == true)
	    	{
	    		if ($transaction_id != null)
	    		{
					$transaction->commit($transaction_id);
				}
				return true;
	    	}
	    	else
	    	{
	    		if ($transaction_id != null)
	    		{
					$transaction->rollback($transaction_id);
				}
	    		return false;
	    	}
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * Removes the Item from a class
     * @todo implementation
     */
    public function unset_class()
    {
    	global $transaction;
    }
     
    /**
     * Sets Item-Information to Class or Item
     * @param string $description
     * @param string $keywords
     * @return bool
     */ 
	public function set_information($description, $keywords)
	{
    	global $transaction;
    	
    	if ($description or $keywords)
    	{
    		$transaction_id = $transaction->begin();
    		
    		$item_information = new ItemInformation(null);
    		if ($item_information->create($description, $keywords) != null)
    		{
    			if ($this->is_item_information() != false or $this->is_item_information() != false)
    			{
	    			if ($this->is_item_information() != false)
	    			{
	    				$item_information->link_item($this->item_id);
		    		}
		    		
		    		if ($this->is_class_information() != false)
		    		{
		    			$item_information->link_class($this->item_class_id);
		    		}
		    		if ($transaction_id != null)
		    		{
						$transaction->commit($transaction_id);
					}
	    			return true;
    			}
    			else
    			{
    				if ($transaction_id != null)
    				{
						$transaction->rollback($transaction_id);
					}
	    			return false;
    			}
    		}
    		else
    		{
    			if ($transaction_id != null)
    			{
					$transaction->rollback($transaction_id);
				}
    			return false;
    		}
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * Checks if an Item needs item-information
     * @return bool
     */
    private function is_item_information()
    {
    	if ($this->sample and is_numeric($this->gid))
    	{
    		$sample_template = new SampleTemplate($this->sample->get_template_id());
    		$gid_item_array = $sample_template->get_gid_item($this->gid);
    		
    		if (is_array($gid_item_array) and count($gid_item_array) >= 1)
    		{
    			foreach($gid_item_array as $key => $value)
    			{
    				if ($value[xml_element] == "information")
    				{
    					$return_array = array();
    							
						if ($value[keywords] == "keywords")
						{
							$return_array[keywords] = true;
						}
						
						if ($value[description] == "description")
						{
							$return_array[description] = true;
						}
						
						if ($return_array[keywords] or $return_array[description])
						{
							return $return_array;
						}
						else
						{
							return false;
						}
					}
    			}
    		}
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * Checks if an Item needs class-information
     * @return bool
     */
    private function is_class_information()
    {
    	if ($this->sample and is_numeric($this->gid))
    	{
    		$sample_template = new SampleTemplate($this->sample->get_template_id());
    		$attribute_array = $sample_template->get_gid_attributes($this->gid);
    		
    		if (is_array($attribute_array) and count($attribute_array) >= 1)
    		{
    			if ($attribute_array['class'])
    			{
    				$class_name = $attribute_array['class'];
    				
    				if ($this->exist_class($class_name))
    				{
    					return false;
    				}
    				else
    				{
	    				$class_array = $sample_template->get_class($class_name);
	    				
	    				if (is_array($class_array) and count($class_array) >= 1)
	    				{
	    					foreach($class_array as $key => $value)
	    					{
	    						if ($value[xml_element] == "information")
	    						{
	    							$return_array = array();
	    							
	    							if ($value[keywords] == "keywords")
	    							{
	    								$return_array[keywords] = true;
	    							}
	    							
	    							if ($value[description] == "description")
	    							{
	    								$return_array[description] = true;
	    							}
	    							
	    							if ($return_array[keywords] or $return_array[description])
	    							{
	    								return $return_array;
	    							}
	    							else
	    							{
	    								return false;
	    							}
	    						}
	    					}
	    				}
	    				else
	    				{
	    					return false;
	    				}
    				}
    			}
    			else
    			{
    				return false;
    			}
    		}
    		else
    		{
    			return false;
    		}    		
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * Checks if an Item needs a description
     * @return bool
     */
    public function is_description()
    {
    	if (is_numeric($this->gid))
    	{
	    	$class_information = $this->is_class_information();
	    	$item_information = $this->is_item_information();
	    	
	    	if ($class_information)
	    	{
				if ($class_information[description] == true)
				{
					return true;
				}
	    	}
	    	elseif($item_information)
	    	{
	    		if ($item_information[description] == true)
	    		{
					return true;
				}
	    	}
	    	else
	    	{
	    		return false;
	    	}
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * Checks if an Item needs keywords
     * @return bool
     */
    public function is_keywords()
    {
    	if (is_numeric($this->gid))
    	{
	    	$class_information = $this->is_class_information();
	    	$item_information = $this->is_item_information();
	    	
	    	if ($class_information)
	    	{
				if ($class_information[keywords] == true)
				{
					return true;
				}
	    	}
	    	elseif($item_information)
	    	{
	    		if ($item_information[keywords] == true)
	    		{
					return true;
				}
	    	}
	    	else
	    	{
	    		return false;
	    	}
    	}
    	else
    	{
    		return false;
    	}
    }
    
    /**
     * Checks if an Item is classified
     * @return bool
     */
    public function is_classified()
    {
    	if ($this->sample and is_numeric($this->gid))
    	{
    		$sample_template = new SampleTemplate($this->sample->get_template_id());
    		if (is_array($attribute_array = $sample_template->get_gid_attributes($this->gid)))
    		{
    			if ($attribute_array['class'])
    			{
    				if ($attribute_array['classify'] == "force")
    				{
    					return $attribute_array['class'];
    				}
    				else
    				{
    					return false;
    				}
    			}
    			else
    			{
    				return false;
    			}
    		}
    		else
    		{
    			return false;
    		}    		
    	}
    	else
    	{
    		return false;
    	}
    }

    /**
     * @param integer $item_id
     * @param integer $sample_id
     * @return integer
     */
    public static function get_gid_by_item_id_and_sample_id($item_id, $sample_id)
    {
    	return SampleHasItem_Access::get_gid_by_item_id_and_sample_id($item_id, $sample_id);
    }
    
	/**
     * @param object $event_object
     * @return bool
     */
    public static function listen_events($event_object)
    {
    	if ($event_object instanceof ItemDeleteEvent)
    	{
			$sample_item = new SampleItem(null);
			$sample_item->set_item_id($event_object->get_item_id());
			if ($sample_item->unlink_item_full() == false)
			{
				return false;
			}
    	}
    	
    	return true;
    }
}
?>