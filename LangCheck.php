<?php
	$Timer_Start = MicroTime( True );
	
	$Data = Filter_Input( INPUT_POST, 'data' );
	
	if( Empty( $Data ) || StrPos( $Data, '[' ) === FALSE )
	{
		Die( '<div class="alert alert-danger">Empty input</div>' );
	}
	
	$Data     = Explode( "\n", $Data );
	$Number   = 0;
	$OriginalLines = [];
	$Errors   = [];
	$SecNames = [];
	$Formatters = [];
	$Section  = "";
	$MultiLine = false;
	
	$FormatterRegex = '/(%(?:\d+\$)?[+-]?(?:[ 0]|\'.{1})?-?\d*(?:\.\d+)?[bcdeEufFgGosxX])/';
	
	$Data[ ] = $Ending = "[" . UniqId( ) . "]";
	
	$FoundLanguages = Array();
	
	ForEach( $Data as $Line )
	{
		$Line = Trim( $Line );
		$Number++;
		
		if( Empty( $Line ) || $Line[ 0 ] === ';' || ( $Line[ 0 ] === '/' && $Line[ 1 ] === '/' ) )
		{
			continue;
		}
		
		if( $Line[ 0 ] === '[' && StrLen( $Line ) >= 3 )
		{
			if( !Empty( $Section ) )
			{
				if( Empty( $SecNames ) )
				{
					AddError( 0, "error", $Section, "It's empty! No languages were found there." );
				}
				else
				{
					ForEach( $Default as $Name => $Dummy )
					{
						if( !Array_Key_Exists( $Name, $SecNames ) )
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
			
			$SecNames = Array( );
			$Section  = SubStr( $Line, 1, 2 );
			
			$FoundLanguages[ strtolower( $Section ) ] = true;
			
			if( $Line[ 3 ] !== ']' )
			{
				AddError( $Number, "warning", $Section, "Found language tag, but 4th character wasn't closing one." .
					"<br>AMXX only takes first 2 characters as identifier. To fix this mistake, change it to" .
					" <b>[".HtmlEntities( $Section )."]</b>" );
				
				continue;
			}
			
			continue;
		}
		
		if( Empty( $Section ) )
		{
			AddError( $Number, "warning", "", "Found line, but no language is set yet." );
			continue;
		}
		
		if( !$MultiLine )
		{
			if( ( $Pos = StrPos( $Line, '=' ) ) === FALSE )
			{
				if( StrPos( $Line, ':' ) === FALSE )
				{
					AddError( $Number, "warning", $Section, "Invalid multi-lingual line." );
				}
				else
				{
					$MultiLine = true;
					
					$Line = Explode( ':', $Line, 2 );
				}
			}
			else
			{
				if( $Line[ $Pos + 1 ] !== ' ' || $Line[ $Pos - 1 ] !== ' ' )
				{
					AddError( $Number, "warning", $Section, "There should be a space before and after <b>=</b> character." );
				}
				
				$Line = Explode( '=', $Line, 2 );
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
		
		$Name = HtmlEntities( Trim( $Line[ 0 ] ) );
		
		if( Array_Key_Exists( $Name, $SecNames ) )
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
			if( !Array_Key_Exists( $Name, $Formatters ) )
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
	
	$KnownLanguages = Array(
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
	);
	
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
	
	$Time = Number_Format( ( MicroTime( True ) - $Timer_Start ), 4, '.', '' );
	
	if( Empty( $Errors ) )
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
		
		$Errors[ $Section ][ ] = Array( $Line, $Type, $Error, $Name );
	}
