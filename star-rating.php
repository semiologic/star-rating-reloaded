<?php
/*
Plugin Name: Star Rating Reloaded
Description: Insert inline rating stars within your posts based on the score you assign, e.g.: [rating:5].
Version: 0.2.6 fork
Author: Yaosan Yeo, Mike Koepke

*/

/*  Original version  Copyright 2006  Yaosan Yeo  (email : eyn@channel-ai.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

foreach ( array(
		'sr_limitstar',
		'sr_defaultstar',
		'sr_prefix',
		'sr_allprefix',
		'sr_suffix',
		'sr_ext',
		'sr_usetext',
		'sr_tscore',
		'sr_counter',
		) as $var )
{
	global $$var;
}

// configurable variables (global variables)

	$sr_limitstar = 0;				// globally limit max number of stars, put 0 if choose not to use
	$sr_defaultstar = 5;			// default max score when not explicitly expressed, e.g. [rating:3.5] is evaluated as 3.5/$sr_defaultstar
	$sr_prefix = "<strong>Rating:</strong> ";				// prefix text before inserting star graphics, can leave blank if desired
	$sr_allprefix = "<strong>Overall Rating:</strong> ";	// prefix used for overall rating
	$sr_suffix = "";				// useful if you want to assign CSS class to star images (note: star images are explicitly coded to have 0 padding and border)
	$sr_ext = "png";				// file extension for star images
	$sr_usetext = 1;				// output plain text instead of star images in posts and feeds, options as below:
									// 0: images for posts and feeds;	1: images for posts, text for feeds;	2: text for all

// end of configurable variables

// global variables for calculating cumulative average
	$sr_tscore = 0;
	$sr_counter = 0;

function ai_addstar($content)
{
	$content = preg_replace_callback( "/(?<!`)\[rating:(([^]]+))]/i", "ai_genstar", $content );
	$content = preg_replace( "/`(\[rating:(?:[^]]+)])`?/i", "$1", $content );
	return $content;
}

function ai_genstar($matches) {
	global $sr_limitstar, $sr_defaultstar, $sr_prefix, $sr_allprefix, $sr_suffix, $sr_ext, $sr_usetext, $sr_tscore, $sr_counter;

	list($score, $maxscore) = explode("/", $matches[2]);

	// check if we should get overall rating
	if ( strncasecmp(trim($score), "overall", 7) == 0 ) {
		$percent = $sr_tscore / $sr_counter;

		if ($maxscore)
			$maxstar = $maxscore;
		else
			$maxstar = $sr_defaultstar;

		$prefix = $sr_allprefix;

		// clear cummulative variables for cases where multiple overall rating is required within single post
		$sr_tscore = 0;
		$sr_counter = 0;
	}

	// if not overall, calculate rating based on score assigned
	else {
		if ($maxscore) {
			// limit max number of stars to 20
			$maxstar = ($maxscore <= 20) ? $maxscore : $sr_defaultstar;
		}
		else {
			$maxscore = $sr_defaultstar;
			$maxstar = $sr_defaultstar;
		}

		// check if we should limit the global max number of stars
		if ($sr_limitstar){
			$maxstar = $sr_limitstar;
		}

		$percent = $score / $maxscore;
		$sr_counter++;
		$sr_tscore += $percent;
		$prefix = $sr_prefix;
	}

	$star = $percent * $maxstar;

	$path = plugin_dir_url(__FILE__);

	// check if half star occurs
	// e.g. [3.75 , 4.25) = 4 stars; [4.25 , 4.75) = 4.5 stars
	$halfstar = "";
	$star = round( $star * 2 );

	if ( $star % 2 ) {
		$halfstar = '<img src="' . $path . 'halfstar.' . $sr_ext . '" alt="&frac12;" style="border:0; padding:0;" />';
		$star = floor( $star / 2 );
		$blankstar = $maxstar - $star - 1;
	}
	else
	{
		$star = $star / 2;
		$blankstar = $maxstar - $star;
	}

	// finally, generate html for rating stars
	$code = $prefix . str_repeat ('<img src="' . $path . 'star.' . $sr_ext . '" alt="&#9733;" style="border:0; padding:0;" />', $star) . $halfstar . str_repeat('<img src="' . $path . 'blankstar.' . $sr_ext . '" alt="&#9734;" style="border:0; padding:0;" />', $blankstar);
	$code .= $sr_suffix;

	// generate alternative plain text output
	if ($halfstar != "")
		$star += 0.5;

	$textcode = $prefix . $star . " out of " . round($maxstar) . " stars" . $sr_suffix;

	// output code based on options
	if ( $sr_usetext == 1 && is_feed() )
		return $textcode;
	elseif ( $sr_usetext == 2 )
		return $textcode;
	else
		return $code;
}

add_filter('the_content', 'ai_addstar');
