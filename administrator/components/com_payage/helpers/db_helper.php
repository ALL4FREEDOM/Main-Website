<?php
/********************************************************************
Product 	: Payage
Date		: 7 October 2016
Copyright	: Les Arbres Design 2014-2016
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

if (class_exists("LAPG_model"))
	return;

class LAPG_model extends JModelLegacy
{

function __construct()
{
	parent::__construct();
}

//-------------------------------------------------------------------------------
// Execute a SQL query and return true if it worked, false if it failed
//
function ladb_execute($query)
{
	try
		{
		$this->_db->setQuery($query);
		$this->_db->execute();
		}
	catch (RuntimeException $e)
		{
	    $this->ladb_error_text = $e->getMessage();
	    $this->ladb_error_code = $e->getCode();
		return false;
		}
	return true;
}

//-------------------------------------------------------------------------------
// Get a single value from the database as an object and return it, or false if it failed
//
function ladb_loadResult($query)
{
	try
		{
		$this->_db->setQuery($query);
		$result = $this->_db->loadResult();
		}
	catch (RuntimeException $e)
		{
	    $this->ladb_error_text = $e->getMessage();
	    $this->ladb_error_code = $e->getCode();
		return false;
		}
	return $result;
}

//-------------------------------------------------------------------------------
// Get a row from the database as an object and return it, or false if it failed
//
function ladb_loadObject($query)
{
	try
		{
		$this->_db->setQuery($query);
		$result = $this->_db->loadObject();
		}
	catch (RuntimeException $e)
		{
	    $this->ladb_error_text = $e->getMessage();
	    $this->ladb_error_code = $e->getCode();
		return false;
		}
	return $result;
}

//-------------------------------------------------------------------------------
// Get an array of rows from the database and return it, or false if it failed
//
function ladb_loadObjectList($query, $limitstart = 0, $limit = 0)
{
	try
		{
		$this->_db->setQuery($query, $limitstart, $limit);
		$result = $this->_db->loadObjectList();
		}
	catch (RuntimeException $e)
		{
	    $this->ladb_error_text = $e->getMessage();
	    $this->ladb_error_code = $e->getCode();
		return false;
		}
	return $result;
}

//-------------------------------------------------------------------------------
// set the database date language
//
function setDbLanguage()
{
	$langObj = JFactory::getLanguage();
	$lang = $langObj->get('tag');
	$lang[2] = '_';
	$this->ladb_execute("SET lc_time_names = '$lang';");
}

//-------------------------------------------------------------------------------
// get the current database time
// can be called from anywhere, not just models
//
static function getDatabaseDateTime()
{
	$db	= JFactory::getDBO();
	$db->setQuery('Select NOW()');
	return $db->loadResult();
}

//-------------------------------------------------------------------------------
// create an insert or update query, using the properties of the object passed in
//  (ignoring properties that start with an underscore)
// $noquote is an optional array of column names not to be quoted
//
function ladb_makeQuery($data, $table_name, $noquote=array())
{
	$comma = '';
	if ($data->id == 0)
		{
		$column_list = '';
		$value_list = '';
		foreach ($data as $column_name => $value)
			{
			if ($column_name == 'id')
				continue;
			if (substr($column_name,0,1) == '_')				// column names beginning with '_' are not stored in the table
				continue;
			$column_list .= $comma."`".$column_name."`";
			if (!in_array($column_name,$noquote))
				$value = $this->_db->Quote($value);
			$value_list .= $comma.$value;
			$comma = ', ';
			}
		$query = "INSERT INTO `".$table_name."` ($column_list) VALUES ($value_list)";
		}
	else
		{
		$update_list = '';
		foreach ($data as $column_name => $value)
			{
			if ($column_name == 'id')
				continue;
			if (substr($column_name,0,1) == '_')				// column names beginning with '_' are not stored in the table
				continue;
			if (!in_array($column_name,$noquote))
				$value = $this->_db->Quote($value);
			$update_list .= $comma."`".$column_name."` = ".$value;
			$comma = ', ';
			}
		$query = "UPDATE `".$table_name."` SET $update_list WHERE `id` = ".$data->id;
		}
	
	return $query;
}


}




