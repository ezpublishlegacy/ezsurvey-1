<?php
//
// Created on: <02-Apr-2004 00:00:00 Jan Kudlicka>
//
// Copyright (C) 1999-2004 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE.GPL included in
// the packaging of this file.
//
// Licencees holding valid "eZ publish professional licences" may use this
// file in accordance with the "eZ publish professional licence" Agreement
// provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" is available at
// http://ez.no/products/licences/professional/. For pricing of this licence
// please contact us via e-mail to licence@ez.no. Further contact
// information is available at http://ez.no/home/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

/*! \file edit.php
*/

include_once( 'kernel/common/template.php' );
include_once( 'extension/ezsurvey/modules/survey/classes/ezsurvey.php' );
include_once( 'kernel/classes/ezcontentcachemanager.php' );
include_once( 'kernel/classes/ezcontentobject.php' );

$http =& eZHTTPTool::instance();

$Module =& $Params['Module'];

$surveyID = '';

if($http->hasPostVariable('SurveyID')){

       $surveyID = $http->postVariable('SurveyID'); 

}else{

       $surveyID =& $Params['SurveyID'];

}

if ( $http->hasPostVariable( 'SurveyDiscardButton' ) )
{
    $Module->redirectTo( '/survey/list' );

    return;

}


$survey =& eZSurvey::fetch( $surveyID );

if ( !$survey )
    return $Module->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );

$surveyList =& $survey->fetchQuestionList();

$validation = array();

$survey->processEditActions( $validation );
if ( $http->hasPostVariable( 'SurveyPublishButton' ) && $validation['error'] == false && $validation['warning'] == false )
{
    $survey->setAttribute( 'published', 1 );

    $survey->storeAll();

    $node = eZContentObjectTreeNode::fetch( $survey->attribute('node_id'));

    $object = $node->attribute('object');

    eZContentCacheManager::clearObjectViewCache( $object->attribute('id'), true );

    //eZContentObject::expireAllCache();
    
    $Module->redirectTo( '/survey/list' );

    return;
}
else
    $survey->sync();

if ( $http->hasPostVariable( 'SurveyPreviewButton' ) && $validation['error'] == false && $validation['warning'] == false )
    $Module->redirectTo( '/survey/preview/'.$surveyID );

foreach ( $surveyList as $question )
{
    if ( $http->hasPostVariable( 'SurveyQuestionCopy_' . $question->attribute( 'id' ) . '_x' ) )
    {
        $question->clone( $surveyID );
        $survey->QuestionList = null;  // Clear the cached list  TODO: cleanup
        $surveyList =& $survey->fetchQuestionList();
        $survey->reOrder();
        break;
    }
}

$tpl =& templateInit();

$tpl->setVariable( 'survey', $survey );

$tpl->setVariable( 'survey_questions', $surveyList );

$tpl->setVariable( 'survey_validation', $validation );

$ini =& eZINI::instance('ezsurvey.ini');

$path_text = $ini->variable( 'PathTextSettings', 'PathText' );

$path_node_id = $ini->variable('PathNodeIDSettings','PathNodeID');

$node = eZContentObjectTreeNode::fetch( $survey->attribute('node_id'));

$tpl->setVariable('node',$node);

$tpl->setVariable('content_template','design:survey/edit.tpl');

$tpl->setVariable('language_code',$node->CurrentLanguage);

$Result = array();

$Result['content'] =& $tpl->fetch( 'design:survey/full.tpl' );

$Result['path']=array();

for($i=0;$i<count($path_text);$i++){

	 $Result['path'][$i]['text']=$path_text[$i];

}

$Result['path'][count($path_text)]['text']=$node->attribute('name');

for($i=0;$i<count($path_node_id);$i++){

         $Result['path'][$i]['node_id']=$path_node_id[$i];

}

$Result['path'][count($path_node_id)]['node_id']=$node->attribute('node_id');

?>
