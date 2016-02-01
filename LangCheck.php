<?php
	$Timer_Start = microtime( true );
	
	$Data = filter_input( INPUT_POST, 'data' );
	
	if( empty( $Data ) || strpos( $Data, '[' ) === false )
	{
		Die( '<div class="alert alert-danger">Your input does not look like a language file that can be scanned.</div>' );
	}
	
	$Data     = explode( "\n", $Data );
	$Number   = 0;
	$OriginalLines = [];
	$Errors   = [];
	$SecNames = [];
	$Formatters = [];
	$FoundLanguages = [];
	$Section   = '';
	$MultiLine = false;
	
	$FormatterRegex = '/(%(?:\d+\$)?[+-]?(?:[ 0]|\'.{1})?-?\d*(?:\.\d+)?[bcdeEufFgGosxX])/';
	
	$Data[ ] = $Ending = '[' . uniqid( ) . ']';
	
	foreach( $Data as $Line )
	{
		$Line = trim( $Line );
		$Number++;
		
		if( empty( $Line ) || $Line[ 0 ] === ';' || ( $Line[ 0 ] === '/' && $Line[ 1 ] === '/' ) )
		{
			continue;
		}
		
		if( $Line[ 0 ] === '[' && strlen( $Line ) >= 3 )
		{
			if( !empty( $Section ) )
			{
				if( empty( $SecNames ) )
				{
					AddError( 0, "error", $Section, "It's empty! No languages were found there." );
				}
				else
				{
					foreach( $OriginalLines as $Name => $Dummy )
					{
						if( !array_key_exists( $Name, $SecNames ) )
						{
							AddError( 0, "error", $Section, "Translation for <b>{$Name}</b> is missing" );
						}
					}
				}
			}
			
			if( $MultiLine )
			{
				$MultiLine = false;
				AddError( $Number, "error", $Section, "New section, multiline unterminated" );
			}
			
			if( $Line === $Ending )
			{
				continue;
			}
			
			$SecNames = [];
			$Section  = substr( $Line, 1, 2 );
			
			$FoundLanguages[ strtolower( $Section ) ] = true;
			
			if( $Line[ 3 ] !== ']' )
			{
				AddError( $Number, "warning", $Section, "Found language tag, but 4th character wasn't closing one." .
					"<br>AMXX only takes first 2 characters as identifier. To fix this mistake, change it to" .
					" <b>[" . htmlentities( $Section ) . "]</b>"
				);
				
				continue;
			}
			
			continue;
		}
		
		if( empty( $Section ) )
		{
			AddError( $Number, "warning", "", "Found line, but no language is set yet." );
			continue;
		}
		
		if( !$MultiLine )
		{
			if( ( $Pos = strpos( $Line, '=' ) ) === false )
			{
				if( strpos( $Line, ':' ) === false )
				{
					AddError( $Number, "warning", $Section, "Invalid multi-lingual line." );
				}
				else
				{
					$MultiLine = true;
					
					$Line = explode( ':', $Line, 2 );
				}
			}
			else
			{
				if( $Line[ $Pos + 1 ] !== ' ' || $Line[ $Pos - 1 ] !== ' ' )
				{
					AddError( $Number, "warning", $Section, "There should be a space before and after <b>=</b> character." );
				}
				
				$Line = explode( '=', $Line, 2 );
			}
		}
		else
		{
			if( $Line[ 0 ] === ':' )
			{
				$MultiLine = false;
			}
			
			continue;
		}
		
		$Name = htmlentities( Trim( $Line[ 0 ] ) );
		
		if( array_key_exists( $Name, $SecNames ) )
		{
			AddError( $Number, "warning", $Section, "Translation for <b>{$Name}</b> already exists for this language." );
			
			continue;
		}
		
		$SecNames[ $Name ] = true;
		
		if( $Section == 'en' )
		{
			$OriginalLines[ $Name ] = $Number;
			
			preg_match_all( $FormatterRegex, $Line[ 1 ], $Formatters[ $Name ] );
		}
		else
		{
			if( !array_key_exists( $Name, $Formatters ) )
			{
				AddError( $Number, "info", $Section, "Default translation for <b>{$Name}</b> does not exist." );
			}
			else
			{
				preg_match_all( $FormatterRegex, $Line[ 1 ], $Test );
				
				if( $Test !== $Formatters[ $Name ] )
				{
					AddError( $Number, "error", $Section, "Mismatching formatting modifiers", $Name );
				}
			}
		}
	}
	
	unset( $SecNames, $Formatters );
	
	$KnownLanguages =
	[
		'en' => 'en',
		'de' => 'de',
		'sr' => 'sr',
		'tr' => 'tr',
		'fr' => 'fr',
		'sv' => 'sv',
		'da' => 'da',
		'pl' => 'pl',
		'nl' => 'nl',
		'es' => 'es',
		'bp' => 'bp',
		'cz' => 'cz',
		'fi' => 'fi',
		'bg' => 'bg',
		'ro' => 'ro',
		'hu' => 'hu',
		'lt' => 'lt',
		'sk' => 'sk',
		'mk' => 'mk',
		'hr' => 'hr',
		'bs' => 'bs',
		'ru' => 'ru',
		'cn' => 'cn'
	];
	
	foreach( $KnownLanguages as $Language )
	{
		if( !array_key_exists( $Language, $FoundLanguages ) )
		{
			AddError( 0, "info", $Language, "Missing language <b>{$Language}</b>." );
		}
	}
	
	foreach( $FoundLanguages as $Language => $Derp )
	{
		if( $Language === 'ls' )
		{
			AddError( 0, "info", $Language, "Please remove leet speak language." );
		}
		
		if( !array_key_exists( $Language, $KnownLanguages ) )
		{
			AddError( 0, "info", $Language, "Unknown language <b>{$Language}</b>." );
		}
	}
	
	unset( $KnownLanguages );
	
	$Time = Number_Format( ( microtime( true ) - $Timer_Start ), 4, '.', '' );
	
	if( empty( $Errors ) )
	{
		echo '<div class="alert alert-success">Congratulations! No errors were found.';
		echo ' Analyzed <span class="text-primary">' . $Number . '</span> lines ';
		echo '(<span class="text-primary">' . Count( $FoundLanguages ) . '</span> languages) in <span class="text-primary">' . $Time . '</span> seconds.</div>';
	}
	else
	{
		echo '<div class="alert alert-danger">Found ' . Count( $Errors ) . ' errors.';
		echo ' Analyzed <span class="text-primary">' . $Number . '</span> lines ';
		echo '(<span class="text-primary">' . Count( $FoundLanguages ) . '</span> languages) in <span class="text-primary">' . $Time . '</span> seconds.</div>';
		
		echo "<ol id=\"error_loop\">";
		
		foreach( $Errors as $Section => $Derp )
		{
			echo "<li class=\"msg_loop\"><h3>Section <b>[".HtmlEntities( $Section )."]</b></h3><ol>";
			
			foreach( $Derp as $Error )
			{
				$Line  = $Error[ 0 ];
				$Type  = $Error[ 1 ];
				$Name  = $Error[ 3 ];
				$Error = $Error[ 2 ];
				
				echo "<li class=\"msg_{$Type}\">";
				echo "<span class=\"err_type\"><img src=\"icon_{$Type}.png\" alt=\"\" width=\"16\" height=\"16\"></span>";
				
				if( $Line > 0 )
				{
					echo "Line <b>{$Line}</b>: ";
				}
				
				echo $Error;
				
				if( $Line > 0 )
				{
					$Line = HtmlEntities( UTF8_Encode( $Data[ --$Line ] ) );
					
					echo "<p><pre>{$Line}</pre></p>";
					
					if( !empty( $Name ) )
					{
						$Line = HtmlEntities( UTF8_Encode( $Data[ $OriginalLines[ $Name ] - 1 ] ) );
						
						echo "<p>English line:<pre>{$Line}</pre></p>";
					}
				}
				
				echo "</li>";
			}
			
			echo "</ol></li>";
		}
		
		echo "</ol>";
		
		/*echo "<textarea class=\"form-control\">[SPOILER=.txt]";
		
		foreach( $Errors as $Section => $Derp )
		{
			echo "Section [B][".HtmlEntities( $Section )."][/B]:\n[LIST]";
			
			foreach( $Derp as $Error )
			{
				$Line  = $Error[ 0 ];
				$Error = $Error[ 2 ];
				
				echo "[*]";
				
				if( $Line > 0 )
					echo "Line [B]{$Line}[/B]: ";
				
				echo Str_Replace( Array( '<br>', '<b>', '</b>' ), Array( "\n", '[B]', '[/B]' ), $Error ) . "\n";
				
				if( $Line > 0 )
				{
					$Line = HtmlEntities( UTF8_Encode( $Data[ --$Line ] ) );
					
					echo "[PHP]{$Line}[/PHP]\n";
				}
			}
			
			echo "[/LIST]\n\n";
		}
		
		echo "[/SPOILER]</textarea>";*/
	}
	
	function AddError( $Line, $Type, $Section, $Error, $Name = "" )
	{
		global $Errors;
		
		$Errors[ $Section ][ ] = [ $Line, $Type, $Error, $Name ];
	}
