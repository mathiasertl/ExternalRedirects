<?php
$wgHooks['ArticleAfterFetchContent'][] = 'ExternalRedirect';

$wgExtensionCredits['other'][] = array(
	'name' => 'ExternalRedirects',
	'description' => 'Allows you to use normal redirects as external redirects',
	'version' => '1.2-1.11.0',
	'author' => 'Mathias Ertl',
	'url' => 'http://pluto.htu.tuwien.ac.at/devel_wiki/index.php/ExternalRedirects',
);

function getTargetInfo( $article )
{
	# get configuration from LocalSettings.php
	global $wgExternalRedirectProtocols;
	$preg_protos = '(' . implode( '|', $wgExternalRedirectProtocols ) .')';
	$preg_expr = '/^#REDIRECT \[\[(' . $preg_protos . '[^(\]\])\|]*)\|?([^(\]\])]*)\]\]/';
	$num = preg_match( $preg_expr, $article->mContent, $matches);
	$target = $matches[1];
	$targetText = $matches[3];
	return array( $num, $target, $targetText );
}

function ExternalRedirect( $article, $content )
{
	global $wgEnableExternalRedirects;
	if ( $wgEnableExternalRedirects != True )
		return true;
	if ( $article->mIsRedirect != 1 )
		return true;
	
	# determine if this is an external redirect and determine target
	$targetInfo = getTargetInfo( $article );
	$num = $targetInfo[0];
	$target = $targetInfo[1];
	$targetText = $targetInfo[2];

	# the redirect-link doesn't start with any of the protocols:
	if ( $num == 0 ) 
		return true;

	# get some important variables:
	global $wgRequest, $wgOut;
	$requestValues = $wgRequest->getValues();

	# sometimes we don't want to redirect.
	# if an action is defined (i.e. when we edit a page!):
	if ( array_key_exists('action', $requestValues) ) 
		return true;

	# if redirect=no is given and we view the redirect:
	if ( array_key_exists('redirect', $requestValues) ) {
		global $wgStylePath, $wgScriptPath;

		#remove the #REDIRECT [[http....]]
		preg_match( '/^(#REDIRECT \[\[[^(\]\])]*\]\])/', $content, $match );
		$pattern = '/' . preg_quote( $match[1], '/' ) . '/';
		$article->mContent = preg_replace( $pattern, '', $article->mContent );

		# that arrow-image:
		$img_src = $wgStylePath . '/common/images/redirectltr.png';
		$img = '<img src="' . $img_src . '" alt="#REDIRECT" />';
		if ( $targetText == '' ) # use target for targetText if no TargetText found"
			$targetText = $target;
		
		# compose the link:
		$link = '<a href="'. $target . '" class="external redirectText" title="'
			. $targetText . '" rel="nofollow">' . $targetText . '</a>';

		# actually add that arrow plus link to target
		$wgOut->addHTML($img . $link);
		return true;
	}

	# we actually do a redirect:
	$wgOut->redirect( $target );

	# required for new MW-checks
	return true;
}
?>
