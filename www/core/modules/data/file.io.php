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
 * File IO Class
 * @package data
 */
class FileIO
{
	private static function detail()
	{
		try
		{
			if ($_GET[file_id])
			{
				$file = new File($_GET[file_id]);
				
				if ($file->is_read_access())
				{
					if ($_GET[version])
					{
						if ($file->exist_file_version($_GET[version]) == false)
						{
							throw new FileVersionNotFoundException("",5);
						}
					}
					
					$template = new Template("languages/en-gb/template/data/file_detail.html");
					
					$folder = new Folder($file->get_toid());
					
					if ($_GET[version] and is_numeric($_GET[version]))
					{
						$file->open_internal_revision($_GET[version]);
						$internal_revision = $_GET[version];
					}
					else
					{
						$internal_revision = $file->get_internal_revision();
					}
					
					$user = new User($file->get_owner_id());
					
					$file_version_array = $file->get_file_internal_revisions();
					
					if (is_array($file_version_array) and count($file_version_array) > 0)
					{	
						$result = array();
						$counter = 1;
					
						$result[0][version] = 0;
						$result[0][text] = "----------------------------------------------";
						
						foreach($file_version_array as $key => $value)
						{
							$file_version = new File($_GET[file_id]);
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
					
					$template->set_var("version",$file->get_version());
					
					$paramquery = $_GET;
					$paramquery[run] = "history";
					$params = http_build_query($paramquery,'','&#38;');	
					
					$template->set_var("version_list_link",$params);
					
					$template->set_var("title",$file->get_name());
					
					$template->set_var("name",$file->get_name());
					$template->set_var("path",$folder->get_object_path());
					
					$template->set_var("size",Misc::calc_size($file->get_size()));
					$template->set_var("size_in_byte",$file->get_size());
					
					$template->set_var("creation_datetime",$file->get_file_datetime());
					$template->set_var("version_datetime",$file->get_datetime());
					$template->set_var("mime_type",$file->get_mime_type());
					$template->set_var("owner",$user->get_full_name(false));
					$template->set_var("checksum",$file->get_checksum());
					$template->set_var("permission",$file->get_permission_string());
					$template->set_var("comment","");
					
					$template->set_var("thumbnail_image","");
					
					$paramquery = $_GET;
					$params = http_build_query($paramquery,'','&#38;');	
					$template->set_var("download_params",$params);
					
					$paramquery = $_GET;
					$paramquery[run] = "update";
					$paramquery[version] = $internal_revision;
					$params = http_build_query($paramquery,'','&#38;');	
					$template->set_var("update_params",$params);
					
					$paramquery = $_GET;
					$paramquery[run] = "update_minor";
					$paramquery[version] = $file->get_internal_revision();
					$params = http_build_query($paramquery,'','&#38;');	
					$template->set_var("update_minor_params",$params);
					
					$paramquery = $_GET;
					$paramquery[run] = "permission";
					$paramquery[nav] = "data";
					$params = http_build_query($paramquery,'','&#38;');	
					$template->set_var("set_permission_params",$params);
					
					
					$template->set_var("write_access",$file->is_write_access());
		
					if ($file->is_control_access() == true or $file->get_owner_id() == $user->get_user_id())
					{
						$template->set_var("change_permission",true);
					}
					else
					{
						$template->set_var("change_permission",false);
					}
					
					$template->set_var("delete_access",$file->is_delete_access());
					
					
					$paramquery = $_GET;
					$paramquery[run] = "delete";
					unset($paramquery[sure]);
					$params = http_build_query($paramquery,'','&#38;');	
					
					$template->set_var("delete_file_params",$params);
					
					
					$paramquery = $_GET;
					$paramquery[run] = "delete_version";
					$paramquery[version] = $internal_revision;
					unset($paramquery[sure]);
					$params = http_build_query($paramquery,'','&#38;');	
					
					$template->set_var("delete_file_version_params",$params);
					
					
					$paramquery = $_GET;
					$paramquery[nav] = "data";
					unset($paramquery[file_id]);
					unset($paramquery[version]);
					unset($paramquery[run]);
					$params = http_build_query($paramquery,'','&#38;');	
					
					$template->set_var("back_link",$params);
					
					$template->output();
				}
				else
				{
					$exception = new Exception("", 2);
					$error_io = new Error_IO($exception, 20, 40, 2);
					$error_io->display_error();
				}
			}
			else
			{
				$exception = new Exception("", 2);
				$error_io = new Error_IO($exception, 20, 40, 3);
				$error_io->display_error();
			}
		}
		catch(FileVersionNotFoundException $e)
		{
			$error_io = new Error_IO($e, 20, 40, 1);
			$error_io->display_error();
		}
	}
		
	private static function upload_to_project()
	{
		if ($_GET[project_id])
		{
			$project_id = $_GET[project_id];
			$project = new Project($project_id);
			$project_security = new ProjectSecurity($project_id);
			
			if ($project_security->is_access(3, false) == true)
			{
				$project_item = new ProjectItem($project_id);
				$project_item->set_gid($_GET[key]);
				$project_item->set_status_id($project->get_current_status_id());
				
				$description_required = $project_item->is_description();
				$keywords_required = $project_item->is_keywords();
				
				if (($description_required and !$_POST[description]) or ($keywords_required and !$_POST[keywords]))
				{
					require_once("core/modules/item/item.io.php");
					ItemIO::information(http_build_query($_GET), $description_required, $keywords_required);
				}
				else
				{
					$template = new Template("languages/en-gb/template/data/file_upload_project.html");
					
					$unique_id = uniqid();
					
					$paramquery = $_GET;
					$paramquery[unique_id] = $unique_id;
					$params = http_build_query($paramquery, '', '&#38;');
					
					$template->set_var("params", $params);
					$template->set_var("unique_id", $unique_id);
					$template->set_var("session_id", $_GET[session_id]);
					
					$template->set_var("keywords", $_POST[keywords]);
					$template->set_var("description", $_POST[description]);
					
					$template->output();
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
			$exception = new Exception("", 4);
			$error_io = new Error_IO($exception, 20, 40, 3);
			$error_io->display_error();
		}
	}
		
	private static function upload_to_sample()
	{
		if ($_GET[sample_id])
		{
			$sample_id = $_GET[sample_id];
			$sample = new Sample($sample_id);
			$sample_security = new SampleSecurity($sample_id);
			
			if ($sample_security->is_access(2, false))
			{
				$sample_item = new SampleItem($sample_id);
				$sample_item->set_gid($_GET[key]);
				
				$description_required = $sample_item->is_description();
				$keywords_required = $sample_item->is_keywords();
				
				if (($description_required and !$_POST[description]) or ($keywords_required and !$_POST[keywords]))
				{
					require_once("core/modules/item/item.io.php");
					ItemIO::information(http_build_query($_GET), $description_required, $keywords_required);
				}
				else
				{
					$template = new Template("languages/en-gb/template/data/file_upload_sample.html");
					
					$unique_id = uniqid();
					
					$paramquery = $_GET;
					$paramquery[unique_id] = $unique_id;
					$params = http_build_query($paramquery, '', '&#38;');
					
					$template->set_var("params", $params);
					$template->set_var("unique_id", $unique_id);
					$template->set_var("session_id", $_GET[session_id]);
					
					$template->set_var("keywords", $_POST[keywords]);
					$template->set_var("description", $_POST[description]);
					
					$template->output();
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
			$exception = new Exception("", 5);
			$error_io = new Error_IO($exception, 20, 40, 3);
			$error_io->display_error();
		}
	}
	
	private static function upload()
	{
		if ($_GET[folder_id])
		{
			$folder = new Folder($_GET[folder_id]);
			
			if ($folder->is_write_access() == true)
			{
				$template = new Template("languages/en-gb/template/data/file_upload.html");
				
				$unique_id = uniqid();
				
				$paramquery = $_GET;
				$paramquery[unique_id] = $unique_id;
				$params = http_build_query($paramquery, '', '&#38;');
				
				$template->set_var("params", $params);
				$template->set_var("unique_id", $unique_id);
				$template->set_var("session_id", $_GET[session_id]);
				
				$template->output();
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
	
	private static function update()
	{
		if ($_GET[file_id])
		{		
			$file = new File($_GET[file_id]);
			
			if ($file->is_write_access())
			{
				$template = new Template("languages/en-gb/template/data/file_update.html");
				
				$unique_id = uniqid();
				
				$paramquery = $_GET;
				$paramquery[unique_id] = $unique_id;
				$params = http_build_query($paramquery, '', '&#38;');
				
				$template->set_var("params", $params);
				$template->set_var("unique_id", $unique_id);
				$template->set_var("session_id", $_GET[session_id]);
				
				$template->output();
			}
			else
			{
				$exception = new Exception("", 2);
				$error_io = new Error_IO($exception, 20, 40, 2);
				$error_io->display_error();
			}	
		}
		else
		{
			$exception = new Exception("", 2);
			$error_io = new Error_IO($exception, 20, 40, 3);
			$error_io->display_error();
		}
	}
		
	private static function delete()
	{
		global $common;
		
		if ($_GET[file_id])
		{
			$file = new File($_GET[file_id]);
			
			if ($file->is_delete_access())
			{
				if ($_GET[sure] != "true")
				{
					$template = new Template("languages/en-gb/template/data/file_delete.html");
					
					$paramquery = $_GET;
					$paramquery[sure] = "true";
					$params = http_build_query($paramquery);
					
					$template->set_var("yes_params", $params);
							
					$paramquery = $_GET;
					$paramquery[run] = "detail";
					unset($paramquery[sure]);
					$params = http_build_query($paramquery);
					
					$template->set_var("no_params", $params);
					
					$template->output();
				}
				else
				{
					$file = new File($_GET[file_id]);
					
					if ($file->delete() == true)
					{
						$paramquery = $_GET;
						$paramquery[nav] = "data";
						unset($paramquery[sure]);
						unset($paramquery[run]);
						unset($paramquery[file_id]);
						$params = http_build_query($paramquery);
								
						$common->step_proceed($params, "Delete File", "Operation Successful" ,null);
					}
					else
					{
						$paramquery = $_GET;
						$paramquery[run] = "detail";
						unset($paramquery[sure]);
						$params = http_build_query($paramquery);
								
						$common->step_proceed($params, "Delete File", "Operation Failed" ,null);
					}			
				}
			}
			else
			{
				$exception = new Exception("", 2);
				$error_io = new Error_IO($exception, 20, 40, 2);
				$error_io->display_error();
			}
		}
		else
		{
			$exception = new Exception("", 2);
			$error_io = new Error_IO($exception, 20, 40, 3);
			$error_io->display_error();
		}
	}
	
	private static function delete_version()
	{
		global $common;
		
		if ($_GET[file_id] and $_GET[version])
		{
			$file = new File($_GET[file_id]);
			
			if ($file->is_delete_access())
			{
				if ($_GET[sure] != "true")
				{
					$template = new Template("languages/en-gb/template/data/file_delete_version.html");
					
					$paramquery = $_GET;
					$paramquery[sure] = "true";
					$params = http_build_query($paramquery);
					
					$template->set_var("yes_params", $params);
							
					$paramquery = $_GET;
					$paramquery[run] = "detail";
					unset($paramquery[sure]);
					$params = http_build_query($paramquery);
					
					$template->set_var("no_params", $params);
					
					$template->output();
				}
				else
				{
					$file = new File($_GET[file_id]);
					
					if (($return_value = $file->delete_version($_GET[version])) != 0)
					{
						if ($return_value == 1)
						{
							$paramquery = $_GET;
							$paramquery[nav] = "file";
							$paramquery[run] = "detail";
							unset($paramquery[sure]);
							unset($paramquery[version]);
							$params = http_build_query($paramquery);
						}
						else
						{
							$paramquery = $_GET;
							$paramquery[nav] = "data";
							unset($paramquery[sure]);
							unset($paramquery[run]);
							unset($paramquery[file_id]);
							$params = http_build_query($paramquery);
						}
						$common->step_proceed($params, "Delete File", "Operation Successful" ,null);
					}
					else
					{
						$paramquery = $_GET;
						$paramquery[nav] = "file";
						$paramquery[run] = "detail";
						unset($paramquery[sure]);
						$params = http_build_query($paramquery);
								
						$common->step_proceed($params, "Delete File", "Operation Failed" ,null);
					}			
				}
			}
			else
			{
				$exception = new Exception("", 2);
				$error_io = new Error_IO($exception, 20, 40, 2);
				$error_io->display_error();
			}
		}
		else
		{
			$exception = new Exception("", 2);
			$error_io = new Error_IO($exception, 20, 40, 3);
			$error_io->display_error();
		}
	}

	/**
	 * @todo empty history error
	 */
	private static function history()
	{
		if ($_GET[file_id])
		{
			$file = new File($_GET[file_id]);
			
			if ($file->is_read_access())
			{
				$template = new Template("languages/en-gb/template/data/file_history.html");
				
				$file = new File($_GET[file_id]);
				
				$template->set_var("title",$file->get_name());
				
				$table_io = new TableIO("OverviewTable");
				
				$table_io->add_row("","symbol",false,16);
				$table_io->add_row("Name","name",false,null);
				$table_io->add_row("Version","version",false,null);
				$table_io->add_row("Date/Time","datetime",false,null);
				$table_io->add_row("Size","size",false,null);
				
				$content_array = array();
				
				$file_version_array = $file->get_file_internal_revisions();
				
				if (is_array($file_version_array) and count($file_version_array))
				{
					foreach($file_version_array as $key => $value)
					{
						$column_array = array();
						
						$file_version = new File($_GET[file_id]);
						$file_version->open_internal_revision($value);
						
						$paramquery = $_GET;
						$paramquery[file_id] = $_GET[file_id];
						$paramquery[version] = $value;
						$paramquery[nav] = "file";
						$paramquery[run] = "detail";
						unset($paramquery[nextpage]);
						$params = http_build_query($paramquery,'','&#38;');
						
						$column_array[symbol][link] = $params;
						$column_array[symbol][content] = "<img src='".$file_version->get_icon()."' alt='' style='border:0;' />";
						$column_array[name][link] = $params;
						
						if (strlen($file_version->get_name()) > 40)
						{
							$column_array[name][content] = substr($file_version->get_name(), 0 , 40)."...";
						}
						else
						{
							$column_array[name][content] = $file_version->get_name();
						}
						
						if ($file_version->is_current() == true)
						{
							$column_array[version] = $file_version->get_version()." <span class='italic'>current</span>";
						}
						else
						{
							$column_array[version] = $file_version->get_version();
						}
						
						$column_array[datetime] = $file_version->get_datetime();
						$column_array[size] = Misc::calc_size($file_version->get_size());
						
						array_push($content_array, $column_array);
					}
				}
				else
				{
					// Error
				}
				
				$table_io->add_content_array($content_array);
				
				$template->set_var("table", $table_io->get_content($_GET[page]));	
				
				$paramquery = $_GET;
				$paramquery[run] = "detail";
				$params = http_build_query($paramquery,'','&#38;');	
				
				$template->set_var("back_link",$params);
				
				$template->output();
			}
			else
			{
				$exception = new Exception("", 2);
				$error_io = new Error_IO($exception, 20, 40, 2);
				$error_io->display_error();
			}
		}
		else
		{
			$exception = new Exception("", 2);
			$error_io = new Error_IO($exception, 20, 40, 3);
			$error_io->display_error();
		}		
	}
	
	public static function method_handler()
	{
		try
		{
			if ($_GET[file_id])
			{
				if (File::exist_file($_GET[file_id]) == false) {
					throw new FileNotFoundException("",2);
				}
			}
		
			switch($_GET[run]):
				case("add_to_project"):
				case("add_project_supplementary_file"):
					self::upload_to_project();
				break;
				
				case("add_to_sample"):
					self::upload_to_sample();
				break;
				
				case("add"):
					self::upload();
				break;
				
				case("update"):
				case("update_minor"):
					self::update();
				break;
	
				case("detail"):
					self::detail();
				break;
				
				case("history"):
					self::history();
				break;
				
				case("delete"):
					self::delete();
				break;
				
				case("delete_version"):
					self::delete_version();
				break;
				
				default:
				
				break;
			endswitch;
		}
		catch (FileNotFoundException $e)
		{
			$error_io = new Error_IO($e, 20, 40, 1);
			$error_io->display_error();
		}
	}

}

?>
