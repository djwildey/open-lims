<?php
/**
 * @package data
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
 * Data IO Class
 * @package data
 */
class DataIO
{
	/**
	 * Remove Exception Dependency
	 */
	public static function browser()
	{
		global $content;
		
		try
		{
			$data_browser = new DataBrowser();
	
			if ($_GET[run] == "delete_stack")
			{
				$data_path = new DataPath(null, null);
				$data_path->delete_stack();
				unset($_GET[run]);
				unset($_GET[vfolder_id]);
			}
			
			if ($_GET[vfolder_id])
			{
				
				if (VirtualFolder::exist_vfolder($_GET[vfolder_id]) == false)
				{
					throw new DataException("",1);
				}
				else
				{
					$virtual_folder_id = $_GET[vfolder_id];
					$folder_id = null;
					$data_path = new DataPath(null, $_GET[vfolder_id]);
				}
			}
			elseif ($_GET[folder_id])
			{
				$folder = new Folder($_GET[folder_id]);
				
				if ($folder->exist_folder() == false)
				{
					throw new DataException("",1);
				}
				else
				{
					if ($folder->is_read_access() == false)
					{
						throw new DataSecurityException("",1);
					}
					else
					{
						$virtual_folder_id = null;
						$folder_id = $_GET[folder_id];
						$data_path = new DataPath($_GET[folder_id], null);
					}
				}
			}
			else
			{
				$data_path = new DataPath(null, null);
				
				if ($_GET[run] == "project_folder" and is_numeric($_GET[project_id]))
				{					
					if (Project::exist_project($_GET[project_id]) == false)
					{
						throw new ProjectException("",1);
					}
					else
					{
						$project_security = new ProjectSecurity($_GET[project_id]);
						
						if ($project_security->is_access(1, false) == false)
						{
							throw new ProjectSecurityException("",1);
						}
						else
						{
							$data_path->init_project_folder($_GET[project_id]);
						}
					}
				}
				
				if ($_GET[run] == "sample_folder" and is_numeric($_GET[sample_id]))
				{
					if (Sample::exist_sample($_GET[sample_id]) == false)
					{
						throw new SampleException("",1);
					}
					else
					{
						$sample_security = new SampleSecurity($_GET[sample_id]);
						if ($sample_security->is_access(1, false) == false)
						{
							throw new SampleSecurityException("",1);
						}
						else
						{
							$data_path->init_sample_folder($_GET[sample_id]);
						}
					}
				}
				
				if ($data_path->get_last_entry_type() == true)
				{
					$virtual_folder_id = $data_path->get_last_entry_id();
					$folder_id = null;
				}
				else
				{
					$virtual_folder_id = null;
					$folder_id = $data_path->get_last_entry_id();
				}
			}
					
			$data_browser_array = $data_browser->get_data_browser_array($folder_id, $virtual_folder_id);
			
			if ($folder_id == null and $virtual_folder_id == null)
			{
				$folder_id = $data_browser->get_folder_id();
			}
			
			
			$content_array = array();
			
			if ($folder_id != 1 or $virtual_folder_id != null)
			{
				$column_array = array();
				
				if ($data_path->get_previous_entry_virtual() == true)
				{			
					$paramquery = $_GET;
					$paramquery[vfolder_id] = $data_path->get_previous_entry_id();
					$paramquery[nav] = "data";
					unset($paramquery[folder_id]);
					unset($paramquery[nextpage]);
					$params = http_build_query($paramquery,'','&#38;');
				}
				else
				{
					$paramquery = $_GET;
					$paramquery[folder_id] = $data_path->get_previous_entry_id();
					$paramquery[nav] = "data";
					unset($paramquery[nextpage]);
					unset($paramquery[vfolder_id]);
					$params = http_build_query($paramquery,'','&#38;');
				}		
				
				$column_array[symbol][link] = $params;
				$column_array[symbol][content] = "<img src='images/icons/parent_folder.png' alt='' style='border:0;' />";
				$column_array[name][link] = $params;
				$column_array[name][content] = "[parent folder]";
				$column_array[type] = "Parent Folder";
				$column_array[version] = "";
				$column_array[datetime] = "";
				$column_array[size] = "";
				$column_array[owner] = "";
				$column_array[permission] = "";
				
				array_push($content_array, $column_array);
			}
	
			$data_browser_array_cardinality = 0;
	
			if ($data_browser_array[0])
			{
				$data_browser_array_cardinality = $data_browser_array_cardinality + count($data_browser_array[0]);
			}
			
			if ($data_browser_array[1])
			{
				$data_browser_array_cardinality = $data_browser_array_cardinality + count($data_browser_array[1]);			
			}
			
			if ($data_browser_array[2])
			{
				$data_browser_array_cardinality = $data_browser_array_cardinality + count($data_browser_array[2]);
			}
			
			if ($data_browser_array[3])
			{
				$data_browser_array_cardinality = $data_browser_array_cardinality + count($data_browser_array[3]);
			}
			
			
			$counter = 0;
	
			if (!$_GET[page] or $_GET[page] == 1)
			{
				$page = 1;
				$counter_begin = 0;
				if ($data_browser_array_cardinality > 25)
				{
					$counter_end = 24;
				}
				else
				{
					$counter_end = $data_browser_array_cardinality-1;
				}
			}
			else
			{
				if ($_GET[page] >= ceil($data_browser_array_cardinality/25))
				{
					$page = ceil($data_browser_array_cardinality/25);
					$counter_end = $data_browser_array_cardinality;
				}
				else
				{
					$page = $_GET[page];
					$counter_end = (25*$page)-1;
				}
				$counter_begin = (25*$page)-25;
			}
	
			if (is_array($data_browser_array) and count($data_browser_array) > 0)
			{
				foreach($data_browser_array as $key => $value)
				{
					switch($key):
					
					// Folder
					case(1):
						if (is_array($value))
						{
							foreach ($value as $sub_key => $sub_value)
							{
								if ($counter >= $counter_begin and $counter <= $counter_end)
								{
									$column_array = array();
									
									if ($sub_value[type] == 0)
									{
										$folder = new Folder($sub_value[id]);
																				
										$user = new User($folder->get_owner_id());
										
										$paramquery = $_GET;
										$paramquery[folder_id] = $sub_value[id];
										$paramquery[nav] = "data";
										unset($paramquery[nextpage]);
										unset($paramquery[vfolder_id]);
										$params = http_build_query($paramquery,'','&#38;');
										
										if ($folder->is_read_access() == true)
										{
											$column_array[symbol][link] = $params;
											$column_array[symbol][content] = "<img src='images/icons/folder.png' alt='' style='border:0;' />";
											$column_array[name][link] = $params;
										}
										else
										{
											$column_array[symbol][link] = "";
											$column_array[symbol][content] = "<img src='core/images/denied_overlay.php?image=images/icons/folder.png' alt='' border='0' />";
											$column_array[name][link] = "";
										}
										
										$column_array[name][content] = $folder->get_name();
										$column_array[type] = "Folder";
										$column_array[version] = "";
										$column_array[datetime] = $folder->get_datetime();
										$column_array[size] = "";
										$column_array[owner] = $user->get_full_name(true);
										$column_array[permission] = $folder->get_permission_string();
										
										$folder_write_access = $folder->is_write_access();
									}
									else
									{
										$virtual_folder = new VirtualFolder($sub_value[id]);
										
										$paramquery = $_GET;
										$paramquery[vfolder_id] = $sub_value[id];
										$paramquery[nav] = "data";
										unset($paramquery[nextpage]);
										$params = http_build_query($paramquery,'','&#38;');
										
										$column_array[symbol][link] = $params;
										$column_array[symbol][content] = "<img src='images/icons/virtual_folder.png' alt='' style='border:0;' />";
										$column_array[name][link] = $params;
										$column_array[name][content] = $virtual_folder->get_name();
										$column_array[type] = "Virtual Folder";
										$column_array[version] = "";
										$column_array[datetime] = $virtual_folder->get_datetime();
										$column_array[size] = "";
										$column_array[owner] = "System";
										$column_array[permission] = "automatic";
										
										$folder_write_access = false;
									}
									array_push($content_array, $column_array);
								}
								$counter++;
							}
						}
					break;
					
					// Files
					case(2):
						if (is_array($value))
						{
							foreach ($value as $sub_key => $sub_value)
							{
								if ($counter >= $counter_begin and $counter <= $counter_end)
								{
									$column_array = array();
									
									$file = new File($sub_value);
									
									$user = new User($file->get_owner_id());
									
									$name = $file->get_name();
									
									if (strlen($name) > 20)
									{
										$name = substr($name,0 ,20)."...";
									}
									
									$paramquery = $_GET;
									$paramquery[file_id] = $sub_value;
									$paramquery[nav] = "file";
									$paramquery[run] = "detail";
									unset($paramquery[nextpage]);
									unset($paramquery[version]);
									$params = http_build_query($paramquery,'','&#38;');
									
									if ($file->is_read_access() == true)
									{
										$column_array[symbol][link] = $params;
										$column_array[symbol][content] = "<img src='".$file->get_icon()."' alt='' style='border:0;' />";
										$column_array[name][link] = $params;
									}
									else
									{
										$column_array[symbol][link] = "";
										$column_array[symbol][content] = "<img src='core/images/denied_overlay.php?image=".$file->get_icon()."' alt='' border='0' />";
										$column_array[name][link] = "";
									}
									
									$column_array[name][content] = $name;
									$column_array[type] = "File";
									$column_array[version] = $file->get_version();
									$column_array[datetime] = $file->get_datetime();
									$column_array[size] = Misc::calc_size($file->get_size());
									$column_array[owner] = $user->get_full_name(true);
									$column_array[permission] = $file->get_permission_string();
									
									array_push($content_array, $column_array);
								}
								$counter++;
							}
						}
					break;
					
					// Values
					case(3):
						if (is_array($value))
						{
							foreach ($value as $sub_key => $sub_value)
							{
								if ($counter >= $counter_begin and $counter <= $counter_end)
								{
									$column_array = array();
			
									$value_class = new Value($sub_value);
			
									$user = new User($value_class->get_owner_id());
									
									$name = $value_class->get_type_name();
									
									if (strlen($name) > 20)
									{
										$name = substr($name,0 ,20)."...";
									}
									
									$paramquery = $_GET;
									$paramquery[value_id] = $sub_value;
									$paramquery[nav] = "value";
									$paramquery[run] = "detail";
									unset($paramquery[nextpage]);
									unset($paramquery[version]);
									$params = http_build_query($paramquery,'','&#38;');
									
									if ($value_class->is_read_access() == true)
									{
										$column_array[symbol][link] = $params;
										$column_array[symbol][content] = "<img src='images/fileicons/16/unknown.png' alt='' style='border:0;' />";
										$column_array[name][link] = $params;
									}
									else
									{
										$column_array[symbol][link] = "";
										$column_array[symbol][content] = "<img src='core/images/denied_overlay.php?image=images/fileicons/16/unknown.png' alt='' border='0' />";
										$column_array[name][link] = "";
									}
									
									$column_array[name][content] = $name;
									$column_array[type] = "Value";
									$column_array[version] = $value_class->get_version();
									$column_array[datetime] = $value_class->get_datetime();
									$column_array[size] = "";
									$column_array[owner] = $user->get_full_name(true);
									$column_array[permission] = $value_class->get_permission_string();
									
									array_push($content_array, $column_array);
								}
								$counter++;
							}
						}
					break;
					
					endswitch;
				}
				$last_line = null;
			}
			else
			{
				$last_line = "This folder is empty.";
			}
			
			$template = new Template("languages/en-gb/template/data/data_browser.html");
	
			if ($folder_id and !$virtual_folder_id)
			{
				$folder = new Folder($folder_id);			
				
				if ($folder->is_write_access() == true)
				{
					$template->set_var("add_file", true);
				}
				else
				{
					$template->set_var("add_file", false);
				}
				
				if ($folder->is_folder_image_content() == true)
				{
					$template->set_var("folder_image", true);
				}
				else
				{
					$template->set_var("folder_image", false);
				}
				
				if ($folder->is_flag_change_permission() or 
					$folder->is_flag_add_folder() or 
					$folder->is_flag_cmd_folder() or 
					$folder->is_flag_rename_folder())
				{
					$template->set_var("folder_administration", true);
				}
				else
				{
					$template->set_var("folder_administration", false);
				}
				
				$template->set_var("item_administration", false);
			}
			else
			{
				$template->set_var("add_file", false);
				$template->set_var("folder_image", false);
				$template->set_var("folder_administration", false);
				$template->set_var("item_administration", false);
			}
			
			$paramquery = $_GET;
			$paramquery[nav] = "data";
			$paramquery[run] = "image_browser_detail";
			$paramquery[folder_id] = $folder_id;
			unset($paramquery[nextpage]);
			$params = http_build_query($paramquery,'','&#38;');
							
			$template->set_var("folder_image_params", $params);
			
			
			$paramquery = $_GET;
			$paramquery[nav] = "file";
			$paramquery[run] = "add";
			$paramquery[folder_id] = $folder_id;
			unset($paramquery[nextpage]);
			$params = http_build_query($paramquery,'','&#38;');
							
			$template->set_var("add_file_params", $params);
	
	
			$paramquery = $_GET;
			$paramquery[run] = "delete_stack";
			unset($paramquery[folder_id]);
			$params = http_build_query($paramquery,'','&#38;');
							
			$template->set_var("home_folder_params", $params);
			
	
			$paramquery = $_GET;
			$paramquery[nav] = "folder";
			$paramquery[run] = "administration";
			$paramquery[folder_id] = $folder_id;
			unset($paramquery[nextpage]);
			$params = http_build_query($paramquery,'','&#38;');
							
			$template->set_var("folder_administration_params", $params);
			
			$paramquery = $_GET;
			$paramquery[nav] = "item";
			$paramquery[run] = "administration_folder";
			$paramquery[folder_id] = $folder_id;
			unset($paramquery[nextpage]);
			$params = http_build_query($paramquery,'','&#38;');
							
			$template->set_var("item_administration_params", $params);		
	
	
			$template->set_var("title","Data Browser");
	
			$table_io = new TableIO("OverviewTable");
			
			$table_io->set_bottom_right_text($data_path->get_stack_path());
			
			$table_io->add_row("","symbol",false,16);
			$table_io->add_row("Name","name",false,null);
			$table_io->add_row("Type","type",false,null);
			$table_io->add_row("Ver.","version",false,null);
			$table_io->add_row("Date/Time","datetime",false,null);
			$table_io->add_row("Size","size",false,null);
			$table_io->add_row("Owner","owner",false,null);
			$table_io->add_row("Permission","permission",false,null);
			
			if ($last_line != null)
			{
				$table_io->override_last_line($last_line);
			}
			
			$table_io->add_content_array($content_array);	
				
			$template->set_var("table", $table_io->get_table($page ,$data_browser_array_cardinality));		
	
			$template->output();
		}
		catch (DataException $e)
		{
			$error_io = new Error_IO($e, 20, 40, 1);
			$error_io->display_error();
		}
		catch (ProjectException $e)
		{
			$error_io = new Error_IO($e, 200, 40, 1);
			$error_io->display_error();
		}
		catch (SampleException $e)
		{
			$error_io = new Error_IO($e, 250, 40, 1);
			$error_io->display_error();
		}
		catch (DataSecurityException $e)
		{
			$error_io = new Error_IO($e, 20, 40, 2);
			$error_io->display_error();
		}
		catch (ProjectSecurityException $e)
		{
			$error_io = new Error_IO($e, 200, 40, 2);
			$error_io->display_error();
		}
		catch (SampleSecurityException $e)
		{
			$error_io = new Error_IO($e, 250, 40, 2);
			$error_io->display_error();
		}
	}

	public static function image_browser_multi()
	{
		if ($_GET[folder_id])
		{
			$folder_id = $_GET[folder_id];
			$folder = new Folder($folder_id);
			
			if ($folder->is_read_access() == true)
			{
				$image_browser_array = DataBrowser::get_image_browser_array($folder_id);
				
				if (is_array($image_browser_array) and count($image_browser_array) >= 1)
				{
					if (!$_GET[page])
					{
						$page = 1;
						$address = 0;
					}
					else
					{
						if ($_GET[page] > count($image_browser_array))
						{
							$page = count($image_browser_array);
							$address = count($image_browser_array)-1;
						}
						else
						{
							$page = $_GET[page];
							$address = $_GET[page]-1;
						}
					}
				
					$template = new Template("languages/en-gb/template/data/data_image_browser_multi.html");
					
					$paramquery = $_GET;
					$paramquery[nav] = "data";
					$paramquery[run] = "image_browser_multi";
					$paramquery[folder_id] = $folder_id;
					unset($paramquery[nextpage]);
					$params = http_build_query($paramquery,'','&#38;');
									
					$template->set_var("multi_params", $params);
					
					
					$paramquery = $_GET;
					$paramquery[nav] = "data";
					$paramquery[run] = "image_browser_detail";
					$paramquery[folder_id] = $folder_id;
					unset($paramquery[nextpage]);
					$params = http_build_query($paramquery,'','&#38;');
									
					$template->set_var("detail_params", $params);
						
					$content_array = array();
					$counter = 0;
					
					for ($i=0;$i<=2;$i++)
					{
						for ($j=0; $j<=3; $j++)
						{
							$current_address = ($address*12)+$counter;
							
							if ($image_browser_array[$current_address])
							{
								$content_array[$counter][display_image] = true;
								
								$file = new File($image_browser_array[$current_address]);
						
								$paramquery[session_id] = $_GET[session_id];
								$paramquery[file_id] = $image_browser_array[$current_address];
								$paramquery[multithumb] = "true";
								$params = http_build_query($paramquery,'','&#38;');
												
								$content_array[$counter][image_params] = $params;
								
								$paramquery = $_GET;
								$paramquery[page] = $current_address+1;
								$paramquery[run] = "image_browser_detail";
								$params = http_build_query($paramquery,'','&#38;');
								
								$content_array[$counter][image_click_params] = $params;
								
								$content_array[$counter][name] = $file->get_name();
								$content_array[$counter][version] = $file->get_version();
							}
							else
							{
								$content_array[$counter][display_image] = false;
							}
							
							if ($j==3)
							{
								$content_array[$counter][display_tr] = true;
							}
							else
							{
								$content_array[$counter][display_tr] = false;
							}
							$counter++;
						}
					}
					
					$template->set_var("content_array", $content_array);
	
					$template->set_var("page_bar",Common_IO::page_bar($page, ceil(count($image_browser_array)/12), $_GET));
	
					$template->output();
				}
				else
				{
					$exception = new Exception("", 4);
					$error_io = new Error_IO($exception, 20, 40, 1);
					$error_io->display_error();
				}
			}
			else
			{
				$exception = new Exception("", 1);
				$error_io = new Error_IO($exception, 20, 40, 2);
				$error_io->display_error();
			}
		}
		else
		{
			$exception = new Exception("", 1);
			$error_io = new Error_IO($exception, 20, 40, 3);
			$error_io->display_error();
		}
	}

	public static function image_browser_detail()
	{
		if ($_GET[folder_id])
		{
			$folder_id = $_GET[folder_id];
			$folder = new Folder($folder_id);
			
			if ($folder->is_read_access() == true)
			{
				$image_browser_array = DataBrowser::get_image_browser_array($folder_id);
				
				if (is_array($image_browser_array) and count($image_browser_array) >= 1)
				{
					if (!$_GET[page])
					{
						$page = 0;
					}
					else
					{
						if ($_GET[page] > count($image_browser_array))
						{
							$page = count($image_browser_array)-1;
						}
						else
						{
							$page = $_GET[page]-1;
						}
					}
				
					if ($image_browser_array[$page])
					{
						$file = new File($image_browser_array[$page]);
				
						$template = new Template("languages/en-gb/template/data/data_image_browser_detail.html");
						
						if ($_GET[version] and is_numeric($_GET[version])) 
						{
							$file->open_internal_revision($_GET[version]);
							$internal_revision = $_GET[version];
						}
						else
						{
							$internal_revision = $file->get_internal_revision();
						}
						
						$file_version_array = $file->get_file_internal_revisions();
						
						if (is_array($file_version_array) and count($file_version_array) > 0)
						{	
							$result = array();
							$counter = 1;
						
							$result[0][version] = 0;
							$result[0][text] = "----------------------------------------------";
							
							foreach($file_version_array as $key => $value)
							{
								$file_version = new File($image_browser_array[$page]);
								$file_version->open_internal_revision($value);
								
								$result[$counter][version] = $file_version->get_internal_revision();
								$result[$counter][text] = "Version ".$file_version->get_version()." - ".$file_version->get_datetime();
								$counter++;
							}
							$template->set_array("version_option",$result);
						}
						
						$result = array();
						$counter = 0;
						
						foreach($_GET as $key => $value)
						{
							if ($key != "version")
							{
								$result[$counter][value] = $value;
								$result[$counter][key] = $key;
								$counter++;
							}
						}
						
						$template->set_array("get",$result);
						
						
						$paramquery = $_GET;
						$paramquery[nav] = "data";
						$paramquery[run] = "image_browser_multi";
						$paramquery[folder_id] = $folder_id;
						$paramquery[page] = floor($page/12)+1;
						unset($paramquery[nextpage]);
						$params = http_build_query($paramquery,'','&#38;');
										
						$template->set_var("multi_params", $params);
						
						
						$paramquery = $_GET;
						$paramquery[nav] = "data";
						$paramquery[run] = "image_browser_detail";
						$paramquery[folder_id] = $folder_id;
						unset($paramquery[nextpage]);
						$params = http_build_query($paramquery,'','&#38;');
										
						$template->set_var("detail_params", $params);
											
						
						$paramquery[session_id] = $_GET[session_id];
						$paramquery[file_id] = $image_browser_array[$page];
						$paramquery[version] = $internal_revision;
						$params = http_build_query($paramquery,'','&#38;');
										
						$template->set_var("image_params", $params);
						
						
						$paramquery[session_id] = $_GET[session_id];
						$paramquery[file_id] = $image_browser_array[$page];
						$paramquery[full] = "true";
						$paramquery[version] = $internal_revision;
						$params = http_build_query($paramquery,'','&#38;');
						
						$template->set_var("image_click_params", $params);
						
						
						$template->set_var("filename",	$file->get_name());
						$template->set_var("version", $file->get_version());
						$template->set_var("datetime", $file->get_datetime());
	
						$template->set_var("page_bar",Common_IO::page_bar($page+1, count($image_browser_array), $_GET));
	
						$template->output();
					
					}
				}
				else
				{
					$exception = new Exception("", 4);
					$error_io = new Error_IO($exception, 20, 40, 1);
					$error_io->display_error();
				}
			}
			else
			{
				$exception = new Exception("", 1);
				$error_io = new Error_IO($exception, 20, 40, 2);
				$error_io->display_error();
			}
		}
		else
		{
			$exception = new Exception("", 1);
			$error_io = new Error_IO($exception, 20, 40, 3);
			$error_io->display_error();
		}
	}

	public static function permission()
	{
		global $common, $user;
		
		try
		{
			if ($_GET[file_id] xor $_GET[value_id])
			{
				if ($_GET[file_id])
				{
					$id = $_GET[file_id];
					$file = new File($id);
					$type = "file";
					$title = $file->get_name();
					if ($file->is_control_access() == true)
					{
						$full_access = true;
						
					}
					else{
						$full_access = false;
					}
					
					if ($file->get_owner_id() == $user->get_user_id())
					{
						$user_access = true;
					}
					else
					{
						$user_access = false;
					}
				}
				
				if ($_GET[value_id])
				{
					$id = $_GET[value_id];
					$value = new Value($id);
					$type = "value";
					$title = $value->get_type_name();
					if ($value->is_control_access() == true)
					{
						$full_access = true;
					}
					else{
						$full_access = false;
					}
					
					if ($value->get_owner_id() == $user->get_user_id())
					{
						$user_access = true;
					}
					else
					{
						$user_access = false;
					}
				}
			}
			else
			{
				if ($_GET[folder_id])
				{
					$id = $_GET[folder_id];
					$folder = new Folder($id);
					$type = "folder";
					$title = $folder->get_name();
					if ($folder->is_control_access() == true)
					{
						$full_access = true;
					}
					else
					{
						$full_access = false;
					}
					
					if ($folder->get_owner_id() == $user->get_user_id())
					{
						$user_access = true;
					}
					else
					{
						$user_access = false;
					}
				}
				else
				{
					throw new IdMissingException("", 0);
				}
			}
			
			if ($full_access == true or $user_access == true)
			{
				$data_permission = new DataPermission($type, $id);
				
				if (!$_GET[nextpage])
				{
					$template = new Template("languages/en-gb/template/data/data_permission.html");
					
					$paramquery = $_GET;
					$paramquery[nextpage] = "1";
					$params = http_build_query($paramquery,'','&#38;');
					
					$template->set_var("params", $params);
					
					$paramquery = $_GET;
					$paramquery[run] = "chown";
					$params = http_build_query($paramquery,'','&#38;');
					
					$template->set_var("params_chown", $params);
					
					$paramquery = $_GET;
					$paramquery[run] = "chgroup";
					$params = http_build_query($paramquery,'','&#38;');
					
					$template->set_var("params_chgroup", $params);
					
					$template->set_var("title", $title);
					
					$user = new User($data_permission->get_owner_id());
					$group = new Group($data_permission->get_owner_group_id());
					
					$template->set_var("owner", $user->get_full_name(false));
					$template->set_var("owner_group", $group->get_name());

					if ($type == "folder")
					{
						if ($folder->is_project_folder() == true or
							$folder->is_project_status_folder() == true or
							$folder->is_sample_folder() == true)
						{
							$disable_automatic = true;
							$disable_project = true;
							$disable_control = true;
							$disable_remain = true;
						}
						elseif($folder->is_child_of_project_folder() == true or
								$folder->is_child_of_sample_folder())
						{
							if ($full_access == true)
							{
								$disable_automatic = false;
								$disable_project = false;
								$disable_control = false;
								$disable_remain = false;
							}
							else
							{
								$disable_automatic = false;
								$disable_project = false;
								$disable_control = true;
								$disable_remain = false;		
							}
						}
						else
						{
							if ($full_access == true)
							{
								$disable_automatic = false;
								$disable_project = true;
								$disable_control = false;
								$disable_remain = false;
							}
							else
							{
								$disable_automatic = false;
								$disable_project = true;
								$disable_control = true;
								$disable_remain = false;	
							}
						}
					}
					else
					{
						if ($type == "file")
						{
							$folder = new Folder($file->get_toid());
						}
						else
						{
							$folder = new Folder($value->get_toid());
						}
						
						if ($folder->is_project_folder() == true or
							$folder->is_sample_folder() == true)
						{
							$disable_automatic = true;
							$disable_project = true;
							$disable_control = true;
							$disable_remain = true;
						}
						elseif($folder->is_child_of_project_folder() == true or
								$folder->is_child_of_sample_folder())
						{
							if ($full_access == true)
							{
								$disable_automatic = false;
								$disable_project = false;
								$disable_control = false;
								$disable_remain = false;
							}
							else
							{
								$disable_automatic = false;
								$disable_project = false;
								$disable_control = true;
								$disable_remain = false;	
							}
						}
						else
						{
							if ($full_access == true)
							{
								$disable_automatic = false;
								$disable_project = true;
								$disable_control = false;
								$disable_remain = false;
							}
							else
							{
								$disable_automatic = false;
								$disable_project = true;
								$disable_control = true;
								$disable_remain = false;	
							}
						}
						
					}
					
					if ($disable_automatic == true)
					{
						$template->set_var("disabled_automatic","disabled='disabled'");
					}
					else
					{
						$template->set_var("disabled_automatic","");
					}
					
					if ($data_permission->get_automatic() == true) {
						$template->set_var("checked_automatic","checked='checked'");
						if ($disable_automatic == true)
						{
							$template->set_var("hidden_automatic","<input type='hidden' name='automatic' value='1' />");
						}
						else
						{
							$template->set_var("hidden_automatic","");
						}
					}else{
						$template->set_var("checked_automatic","");
					}
					
					
					
					$permission_array = $data_permission->get_permission_array();
		
					for ($i=1;$i<=4;$i++)
					{
						for ($j=1;$j<=4;$j++)
						{
							$checked_name = "checked_".$i."_".$j;
							$disabled_name = "disabled_".$i."_".$j;
							$hidden_name = "hidden_".$i."_".$j;
							
							if ($i==3 and $disable_project == true)
							{
								$template->set_var($disabled_name,"disabled='disabled'");
								$disabled = true;
							}
							else
							{
								if (($j==3 or $j==4) and $disable_control == true)
								{
									$template->set_var($disabled_name,"disabled='disabled'");
									$disabled = true;
								}
								else
								{
									if ($disable_remain == true)
									{
										$template->set_var($disabled_name,"disabled='disabled'");
										$disabled = true;
									}
									else
									{
										$template->set_var($disabled_name,"");
										$disabled = false;
									}
								}
							}
							
							if ($permission_array[$i][$j] == true)
							{
								$template->set_var($checked_name,"checked='checked'");
								if ($disabled == true)
								{
									$template->set_var($hidden_name, "<input type='hidden' name='".$checked_name."' value='1' />");
								}
								else
								{
									$template->set_var($hidden_name, "");
								}
							}
							else
							{
								$template->set_var($checked_name,"");
								$template->set_var($hidden_name, "");
							}
							$disabled = false;
						}
					}
	
					$paramquery = $_GET;
					$paramquery[nav] = "data";
					unset($paramquery[run]);
					$params = http_build_query($paramquery,'','&#38;');
					
					$template->set_var("back_link", $params);
					
					$template->output();	
				}
				else
				{
					if ($_POST[save])
					{
						$paramquery = $_GET;
						unset($paramquery[nextpage]);
						$params = http_build_query($paramquery,'','&#38;');
					}
					else
					{
						if ($type == folder)
						{
							$paramquery = $_GET;
							$paramquery[nav] = "data";
							$paramquery[run] = "detail";
							unset($paramquery[nextpage]);
							$params = http_build_query($paramquery,'','&#38;');
						}
						else
						{
							$paramquery = $_GET;
							$paramquery[nav] = $type;
							$paramquery[run] = "detail";
							unset($paramquery[nextpage]);
							$params = http_build_query($paramquery,'','&#38;');
						}
					}
					
					if ($data_permission->set_permission_array($_POST) == true)
					{
						$common->step_proceed($params, "Permission: ".$title."", "Changes saved succesful" ,null);
					}
					else
					{
						$common->step_proceed($params, "Permission: ".$title."", "Operation failed" ,null);
					}
				}
			}
			else
			{
				switch ($type):
				
					case "folder":
						$exception = new Exception("", 1);
					break;
					
					case "file":
						$exception = new Exception("", 2);
					break;
					
					case "value":
						$exception = new Exception("", 3);
					break;
				
				endswitch;
				
				$error_io = new Error_IO($exception, 20, 40, 2);
				$error_io->display_error();
			}
		}
		catch (IdMissingException $e)
		{
			$error_io = new Error_IO($e, 20, 40, 3);
			$error_io->display_error();
		}
	}

	public static function change_owner()
	{
		global $common;
		
		try
		{
			if ($_GET[file_id] xor $_GET[value_id])
			{
				if ($_GET[file_id])
				{
					$id = $_GET[file_id];
					$file = new File($id);
					$type = "file";
					$title = $file->get_name();
					$access = $file->is_control_access();
				}
				if ($_GET[value_id])
				{
					$id = $_GET[value_id];
					$value = new Value($id);
					$type = "value";
					$title = $value->get_type_name();
					$access = $value->is_control_access();
				}
			}
			else
			{
				if ($_GET[folder_id])
				{
					$id = $_GET[folder_id];
					$folder = new Folder($id);
					$type = "folder";
					$title = $folder->get_name();
					$access = $folder->is_control_access();
				}
				else
				{
					throw new IdMissingException("", 0);
				}
			}
			
			if ($access == true)
			{
				$data_permission = new DataPermission($type, $id);
				
				if (!$_GET[nextpage])
				{
					$template = new Template("languages/en-gb/template/data/data_change_owner.html");
					
					$paramquery = $_GET;
					$paramquery[nextpage] = "1";
					$params = http_build_query($paramquery,'','&#38;');
					
					$template->set_var("params",$params);
					
					$template->set_var("title",$title);
					$template->set_var("error","");
					
					$user_array = User::list_entries();
					
					$result = array();
					$counter = 0;
					
					foreach($user_array as $key => $value)
					{
						$user = new User($value);
						$result[$counter][value] = $value;
						$result[$counter][content] = $user->get_username()." (".$user->get_full_name(false).")";
						$counter++;
					}
					
					$template->set_array("option",$result);
					
					$paramquery = $_GET;
					$paramquery[run] = "permission";
					unset($paramquery[nextpage]);
					$params = http_build_query($paramquery,'','&#38;');
					
					$template->set_var("back_link", $params);
					
					$template->output();
				}
				else
				{
					$paramquery = $_GET;
					$paramquery[run] = "permission";
					unset($paramquery[nextpage]);
					$params = http_build_query($paramquery,'','&#38;');
					
					if ($data_permission->set_owner_id($_POST[user]) == true)
					{
						$common->step_proceed($params, "Permission: ".$title."", "Changes saved succesful" ,null);
					}
					else
					{
						$common->step_proceed($params, "Permission: ".$title."", "Operation failed" ,null);
					}
				}
			}
			else
			{
				switch ($type):
				
					case "folder":
						$exception = new Exception("", 1);
					break;
					
					case "file":
						$exception = new Exception("", 2);
					break;
					
					case "value":
						$exception = new Exception("", 3);
					break;
				
				endswitch;
				
				$error_io = new Error_IO($exception, 20, 40, 2);
				$error_io->display_error();
			}
		}
		catch (IdMissingException $e)
		{
			$error_io = new Error_IO($e, 20, 40, 3);
			$error_io->display_error();
		}
	}
	
	public static function change_group()
	{
		global $common;
		
		try
		{
			if ($_GET[file_id] xor $_GET[value_id])
			{
				if ($_GET[file_id])
				{
					$id = $_GET[file_id];
					$file = new File($id);
					$type = "file";
					$title = $file->get_name();
					$access = $file->is_control_access();
				}
				if ($_GET[value_id])
				{
					$id = $_GET[value_id];
					$value = new Value($id);
					$type = "value";
					$title = $value->get_type_name();
					$access = $value->is_control_access();
				}
			}
			else
			{
				if ($_GET[folder_id])
				{
					$id = $_GET[folder_id];
					$folder = new Folder($id);
					$type = "folder";
					$title = $folder->get_name();
					$access = $folder->is_control_access();
				}
				else
				{
					throw new IdMissingException("", 0);
				}
			}
			
			if ($access == true)
			{
				$data_permission = new DataPermission($type, $id);
				
				if (!$_GET[nextpage])
				{
					$template = new Template("languages/en-gb/template/data/data_change_group.html");
					
					$paramquery = $_GET;
					$paramquery[nextpage] = "1";
					$params = http_build_query($paramquery,'','&#38;');
					
					$template->set_var("params",$params);
					
					$template->set_var("title",$title);
					$template->set_var("error","");
					
					$group_array = Group::list_groups();
					
					$result = array();
					$counter = 0;
					
					foreach($group_array as $key => $value)
					{
						$group = new Group($value);
						$result[$counter][value] = $value;
						$result[$counter][content] = $group->get_name();
						$counter++;
					}
					
					$template->set_array("option",$result);
					
					$paramquery = $_GET;
					$paramquery[run] = "permission";
					unset($paramquery[nextpage]);
					$params = http_build_query($paramquery,'','&#38;');
					
					$template->set_var("back_link", $params);
					
					$template->output();
				}
				else
				{
					$paramquery = $_GET;
					$paramquery[run] = "permission";
					unset($paramquery[nextpage]);
					$params = http_build_query($paramquery,'','&#38;');
					
					if ($data_permission->set_owner_group_id($_POST[group]) == true)
					{
						$common->step_proceed($params, "Permission: ".$title."", "Changes saved succesful" ,null);
					}
					else
					{
						$common->step_proceed($params, "Permission: ".$title."", "Operation failed" ,null);
					}
				}
			}
			else
			{
				switch ($type):
				
					case "folder":
						$exception = new Exception("", 1);
					break;
					
					case "file":
						$exception = new Exception("", 2);
					break;
					
					case "value":
						$exception = new Exception("", 3);
					break;
				
				endswitch;
				
				$error_io = new Error_IO($exception, 20, 40, 2);
				$error_io->display_error();
			}
		}
		catch (IdMissingException $e)
		{
			$error_io = new Error_IO($e, 20, 40, 3);
			$error_io->display_error();
		}
	}

	public static function method_handler()
	{	
		switch($_GET[run]):
			case("permission"):
				self::permission();
			break;
			
			case("chown"):
				self::change_owner();
			break;
			
			case("chgroup"):
				self::change_group();
			break;

			case("image_browser_detail"):
				self::image_browser_detail();
			break;
			
			case("image_browser_multi"):
				self::image_browser_multi();
			break;

			default:
				self::browser();
			break;
			
		endswitch;	
	}
	
}

?>
