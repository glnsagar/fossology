#!/usr/bin/php
<?php
/*
 Copyright (C) 2011 Hewlett-Packard Development Company, L.P.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 version 2 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * \brief debug diff env/path when running with exec
 *
 * @version "$Id: $"
 * Created on Aug 24, 2011 by Mark Donohoe
 */

global $failures;

/**
 * \brief checkCUnit report for a failure
 *
 * @return mixed Null on success or an array of SimpleXMLElements or an array
 * with either an error or an exception message.
 *
 * NOTE: I don't really like these return values... fix later.
 */
function checkCUnit($fileName)
{

  if(empty($fileName))
  {
    return(array("Error! illegal input $fileName"));
  }
  try {
    $verFail = check4CUnitFail($fileName);
    if(!is_null($verFail))
    {
      return($verFail);
      //print_r($verFail) . "\n";
      //$failures++;
    }
  }
  catch (Exception $e)
  {
    return(array("Error! $e\n"));
  }
} // checkCUnit

/**
 * \brief transform the xml to html for CUnit style reports
 *
 * @param string $fileName the xml file to transform
 *
 * @return
 */
function genCunitRep($fileName)
{
  if(empty($fileName))
  {
    return(FALSE);
  }
  // get List or Run string so the correct xsl file is used.

  $xslFile = "CUnit-Run.xsl";   // default
  if(preg_grep("/Results/", array($fileName)))
  {
    $xslFile = "CUnit-Run.xsl";
  }
  else if(preg_grep("/Listing/", array($fileName)))
  {
    $xslFile = "CUnit-List.xsl";
  }
  // remove .xml from name
  $outFile = basename($fileName, '.xml');
  $outPath = TESTROOT . "/reports/$outFile.html";
  $xslPath = "/usr/share/CUnit/$xslFile";
  echo "DB: Starting to generate html report for $fileName\n";
  $report = genHtml($fileName, $outPath, $xslPath);
  if(!empty($report))
  {
    echo "Error: Could not generate a HTML Test report from $fileName.\n";
    echo "DB: report is:\n$report\n";
    return(FALSE);
  }
  else
  {
    echo "DB: Generated html file:$outFile" . ".html\n";
  }



  return(TRUE);
}

/**
 * \brief transform the xml to html for XUnit style reports
 *
 * @param string $fileName the xml file to transform
 */
function genXunitRep($fileName)
{
  return(TRUE);
}

/**
 * \brief meta function to process cunit reports
 *
 * @param string $unitTest the unit test to process
 *
 * @return mixed NULL on success, newline terminated string on failure
 */
function processReports($unitTest)
{
  global $failures;

  if(empty($unitTest))
  {
    return("Error! no valid in put at line " . __FILE__ . " at line " .
    __LINE__ . "\n");
  }

  foreach(glob("$unitTest*.xml") as $fileName)
  {
    //echo "DBPROCR: fileName is:$fileName\n";
    if(!tweakCUnit($fileName))
    {
      return("Error! could not save processed xml file, they may not display properly\n");
    }

    //echo "DB: after tweak, we are at:\n" . getcwd() . "\n";
    $errors = array();
    $errors = checkCUnit($fileName);
    //echo "DB: after checkCUnit, we are at:\n" . getcwd() . "\n";
    if(is_object($errors[0]))
    {
      $failures++;
      //return("There were Unit Test Failures in $unitTest\n");
      //print_r($errors) . "\n";
    }
    else if(!is_NULL($errors))
    {
      // if we can't even check the file, then skip making the report
      $failures++;
      return("Failure: Could not check file $fileName for failures\n");
      //echo $errors[0] . "\n";
      //backToParent('../../..');   // back to ..fossology/src
    }

    if(!genCunitRep($fileName))
    {
      //$failures++;
      return("Error!, could not generate html report for $unitTest\n");
    }
  } // foreach

  return(NULL);
} // processReports

/**
 * \breif change the references to the dtd's for cunit reports so they can be
 * processed.
 *
 * @param string $fileName, the path to the filename to process....
 *
 * @return boolean
 */
function tweakCUnit($fileName)
{
  if(empty($fileName))
  {
    return(FALSE);
  }

  //echo "DB: tweaking xml file:$fileName\n";
  //echo "DB: we are at:\n" . getcwd() . "\n";
  $rFile = file_get_contents($fileName);
  //echo "DB: rFile after read:\n$rFile\n";
  // fix the Ref to xsl file
  $pat = '#href="#';
  $replace = 'href="http://fossology.usa.hp.com/~fossology/dtds/';
  $rFile = preg_replace($pat, $replace, $rFile);
  //fix the Ref to the dtds for both run and list files.
  $runPat = '/CUnit-Run\.dtd/';
  $rReplace = 'http://fossology.usa.hp.com/~fossology/dtds/CUnit-Run.dtd';
  $listPat = '/CUnit-List\.dtd/';
  $lReplace = 'http://fossology.usa.hp.com/~fossology/dtds/CUnit-List.dtd';
  $rFile =  preg_replace($runPat, $rReplace, $rFile);
  $rFile =  preg_replace($listPat, $lReplace, $rFile);
  //echo "DB: rFile after preg_replace is:\n$rFile\n";
  //echo "DB: file name to write is:$fileName\n";
  if(!file_put_contents($fileName, $rFile))
  {
    return(FALSE);
  }
  else
  {
    return(TRUE);
  }
} // tweakCUnit

if(!defined('TESTROOT'))
{
  $here = getcwd();
  // set to ...fossology/src/testing
  $fossology = 'fossology';
  $startLen = strlen($fossology);
  $startPos = strpos($here, $fossology);
  if($startPos === FALSE)
  {
    echo "FATAL! did not find fossology, are you cd'd into the sources?\n";
    exit(1);
  }

  $path = substr($here, 0, $startPos+$startLen);
  $trPath = $path . '/src/testing';
  define('TESTROOT',$trPath);
}

$WORKSPACE = NULL;

if(array_key_exists('WORKSPACE', $_ENV))
{
  $WORKSPACE = $_ENV['WORKSPACE'];
}

$unit = TESTROOT . "/unit";

if(@chdir($unit) === FALSE)
{
  echo "FATAL!, could not cd to:" . TESTROOT . "/unit\n";
  exit(1);
}
require_once('../lib/common-Report.php');
require_once('../lib/common-Test.php');

$modules = array();
$unitList = array();

// get the list of unit tests to run
$modules = parse_ini_file('../dataFiles/unitTests.ini',1);
foreach($modules as $key => $value)
{
  $unitList[] = $key;
}

// @todo fix this, I don't think you need to check for workspace.
if(is_null($WORKSPACE))
{
  // back to fossology/src
  backToParent('../..');
}
else
{
  if(@chdir($WORKSPACE . "/fossology/src") === FALSE)
  {
    echo "FATAL! __FILE__ could not cd to " . $WORKSPACE . "/fossology/src\n";
    exit(1);
  }
}

$failures = 0;
foreach($unitList as $unitTest)
{
  
  echo "\n";
  //echo "\n***** Next Test *****\n";
  //echo "DB: we are at:\n" . getcwd() . "\n";
  $makeOut = array();
  $makeRtn = -777;
  $makeCover = array();
  $makeRtn = -777;

  if(@chdir($unitTest . '/agent_tests/') === FALSE)
  {
    echo "Error! cannot cd to " . $unitTest . "/agent_tests, skipping test\n";
    $failures++;
    continue;
  }
  //echo "DB: Before makes we are at:\n" . getcwd() . "\n";
  $cleanMake = exec('make clean 2>&1', $cleanOut, $cleanRtn);
  if($cleanRtn != 0)
  {
    echo "Make clean of $unitTest did not succeed, return code:$cleanRtn\n";
  }
  $lastMake = exec('make test 2>&1', $makeOut, $makeRtn);
  echo "Exit status of 'make test' of $unitTest is:$makeRtn\n";
  if($makeRtn != 0)
  {
    $found = array();
    $found = preg_grep('/No rule to make target/', $makeOut);
    if($found)
    {
      echo "No Unit Tests for module $unitTest\n";
      backToParent('../..');
      continue;
    }
    else
    {
      echo "Error! tests did not make for $unitTest\n";
      //echo "DB: Make Transcript is:\n";
      //$makeErrors =  implode("\n", $makeOut);
      //echo $makeErrors . "\n\n";
      $failures++;
    }
    // some makefiles/tests are written to report a make 'failure' when a test
    // fails, so process the reports, as there should be a xml file.
    //echo "DB: AFTER makes != 0 we are at:\n" . getcwd() . "\n";
    $unitDir = getcwd() . '/Unit';
    if(@chdir($unitDir) === FALSE)
    {
      echo "Error! Could not cd to " . getcwd() . "/unit, skipping reports\n";
      backToParent('../..');
      continue;
    }
    if(!is_NULL($error = processReports($unitTest)))
    {
      echo $error;
    }
    backToParent('../../..');
    continue;
  }
  else
  {
    // no tests for is module?  Skip report processing
    $nothing = array();
    $nothing= preg_grep('/Nothing to be done for/', $makeOut);
    $noTests = array();
    $noTests= preg_grep('/NO TESTS/', $makeOut);
    if($nothing or $noTests)
    {
      echo "No Unit Tests for module $unitTest\n";
      backToParent('../..');
      continue;
    }
    // at this point there should be a .xm file to process
    //echo "DB: AFTER make=0 we are at:\n" . getcwd() . "\n";
    $unitDir = getcwd() . '/Unit';
    if(@chdir($unitDir) === FALSE)
    {
      echo "Error! Could not cd to " . getcwd() . "/Unit, skipping reports\n";
      backToParent('../../..');
      continue;
    }
    if(!is_NULL($error = processReports($unitTest)))
    {
      echo $error;
    }
    backToParent('../../..');
  } // else no make errors
} // for
if($failures)
{
  exit(1);
}
exit(0);

?>