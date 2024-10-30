<?php

/*  Copyright 2015  Michelle Retzlaff  (email : michelle@hootproof.de)
*/


//dummy variables in order to make severities and categories translatable
$hpwc_hootproof_severities = array(
   __('High', 'hootproof-check'),
   __('Medium', 'hootproof-check'),
   __('Low', 'hootproof-check')
);
$hpwc_hootproof_categories = array(
   __('SEO', 'hootproof-check'),
   __('Privacy', 'hootproof-check'),
   __('Legal', 'hootproof-check'),
   __('Performance', 'hootproof-check'),
   __('Security', 'hootproof-check')
);

 global $hpwc_hootproof_result_details;
 $hpwc_hootproof_result_details = array (
      
'inst_broken-link-checker' => array (
  'name' => __('Install plugin: broken-link-checker', 'hootproof-check'),
  'category' => 'SEO',
  'severity' => 'Medium',
  'link' => '' 
),
'inst_subscribe-to-doi-comments' => array (
  'name' => __('Install plugin: subscribe-to-doi-comments', 'hootproof-check'),
  'category' => 'Privacy',
  'severity' => 'Significant',
  'link' => '' 
),
'inst_limit-login-attempts' => array ( 
'name' => __('Install plugin: limit-login-attempts', 'hootproof-check'),
  'category' => 'Security',
  'severity' => 'Significant',
  'link' => '' 
),
'act_broken-link-checker' => array ( 
'name' => __('Activate plugin: broken-link-checker', 'hootproof-check'),
  'category' => 'SEO',
  'severity' => 'Medium',
  'link' => '' 
),
'act_limit-login-attempts' => array ( 
'name' => __('Activate plugin: limit-login-attempts', 'hootproof-check'),
  'category' => 'Security',
  'severity' => 'Significant',
  'link' => '' 
),
'act_subscribe-to-doi-comments' => array ( 
'name' => __('Activate plugin: subscribe-to-doi-comments', 'hootproof-check'),
  'category' => 'Privacy',
  'severity' => 'Significant',
  'link' => '' 
),

'debug_on' => array ( 
'name' => __('Turn off WP_DEBUG', 'hootproof-check'),
  'category' => 'Security',
  'severity' => 'Significant',
  'link' => 'https://hootproof.de/wp_debug-ausstellen/' 
),
'standard_blog_description' => array ( 
'name' => __('Set blog description to something unique', 'hootproof-check'),
  'category' => 'SEO',
  'severity' => 'Siginificant',
  'link' => 'https://hootproof.de/seitenuntertitel-verfassen/' 
),
'sample_page' => array ( 
'name' => __('Delete sample page', 'hootproof-check'),
  'category' => 'SEO',
  'severity' => 'Medium',
  'link' => 'https://hootproof.de/beispiel-seite-loeschen/' 
),
'sample_post' => array ( 
'name' => __('Delete sample post', 'hootproof-check'),
  'category' => 'SEO',
  'severity' => 'Medium',
  'link' => 'https://hootproof.de/beispiel-beitrag-loeschen/' 
),
'sample_comment' => array ( 
'name' => __('Delete sample comment', 'hootproof-check'),
  'category' => 'SEO',
  'severity' => 'Medium',
  'link' => 'https://hootproof.de/beispiel-kommentar-loeschen/' 
),
'hello_dolly' => array ( 
'name' => __('Delete Hello Dolly plugin', 'hootproof-check'),
  'category' => 'General',
  'severity' => 'Low',
  'link' => 'https://hootproof.de/hello-dolly-plugin-loeschen/' 
),
'missing_imprint' => array ( 
'name' => __('Create an imprint', 'hootproof-check'),
  'category' => 'Legal',
  'severity' => 'Siginificant',
  'link' => 'https://hootproof.de/impressum-erstellen/' 
),
'no_blog_description' => array ( 
'name' => __('Fill in your blog description', 'hootproof-check'),
  'category' => 'SEO',
  'severity' => 'Medium',
  'link' => 'https://hootproof.de/seitenuntertitel-verfassen/' 
),
'many_plugins' => array ( 
'name' => __('Reduce number of active plugins', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => 'Medium',
  'link' => 'https://hootproof.de/anzahl-aktiver-plugins-reduzieren/' 
),
'registration_enabled' => array ( 
'name' => __('Prevent users from registering with a sensitive role', 'hootproof-check'),
  'category' => 'Security',
  'severity' => 'Siginificant',
  'link' => 'https://hootproof.de/benutzer-registrierung-einschraenken/' 
),
'no_index' => array ( 
'name' => __('Make blog visible to search engines', 'hootproof-check'),
  'category' => 'SEO',
  'severity' => 'Siginificant',
  'link' => 'https://hootproof.de/blog-fuer-suchmaschinen-sichtbar-machen/' 
),
'standard_permalinks' => array ( 
'name' => __('Set permalink structure', 'hootproof-check'),
  'category' => 'SEO',
  'severity' => 'Siginificant',
  'link' => 'https://hootproof.de/permalink-struktur-setzen/' 
),
'missing_privacy_statement' => array ( 
'name' => __('Create a privacy statement', 'hootproof-check'),
  'category' => 'Legal',
  'severity' => 'Siginificant',
  'link' => 'https://hootproof.de/datenschutzerklaerung-erstellen/' 
),
'ga_anonymize' => array ( 
'name' => __('Activate anonymize IP for Google Analytics', 'hootproof-check'),
  'category' => 'Privacy',
  'severity' => 'Siginificant',
  'link' => 'https://hootproof.de/anonymisierte-ip-adressen-fuer-google-analytics-aktivieren/' 
),
'comment_ips' => array ( 
  'name' => __('Delete IP addresses for %s comments', 'hootproof-check'),
  'name_singular' => __('Delete IP address for %s comment', 'hootproof-check'),
  'category' => 'Privacy',
  'severity' => 'Medium',
  'link' => 'https://hootproof.de/ip-adressen-bei-kommentaren-loeschen/' 
),
/* GTMetrix */
'BrowserCache' => array ( 
'name' => __('Leverage browser caching', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/browser-caching-ermoeglichen/' 
),
'MinifyJS' => array ( 
'name' => __('Minify JavaScript', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/javascript-minimieren/' 
),
'OptImgs' => array ( 
'name' => __('Optimize images', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/bilder-optimieren/' 
),
'MinRedirect' => array ( 
'name' => __('Minimize redirects', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/weiterleitungen-reduzieren/' 
),
'UnusedCSS' => array ( 
'name' => __('Remove unused CSS', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/unnoetiges-css-entfernen/' 
),
'CookieSize' => array ( 
'name' => __('Minimize cookie size', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/cookie-groesse-reduzieren/' 
),
'Gzip' => array ( 
'name' => __('Enable gzip compression', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/gzip-kompression-aktivieren/' 
),
'CombineJS' => array ( 
'name' => __('Combine external JavaScript', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/externes-javascript-zusammenfassen/' 
),
'MinDns' => array ( 
'name' => __('Minimize DNS lookups', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/dns-lookups-reduzieren/' 
),
'ProxyCache' => array ( 
'name' => __('Leverage proxy caching', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/proxy-caching-ermoeglichen/' 
),
'DupeRsrc' => array ( 
'name' => __('Serve resources from a consistent URL', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/ressourcen-von-einer-konsistenten-url-liefern/' 
),
'CssExpr' => array ( 
'name' => __('Avoid CSS expressions', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/css-ausdruecke-vermeiden/' 
),
'ImgDims' => array ( 
'name' => __('Specify image dimensions', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/bilddimensionen-angeben/' 
),
'CssInHead' => array ( 
'name' => __('Put CSS in the document head', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/css-im-kopfbereich-der-seite-platzieren/' 
),
'CssSelect' => array ( 
'name' => __('Use efficient CSS selectors', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/effiziente-css-selektoren-verwenden/' 
),
'DeferParsingJavaScript' => array ( 
'name' => __('Defer loading of JavaScript', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/javascript-verzoegert-laden/' 
),
'CombineCSS' => array ( 
'name' => __('Combine external CSS', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/externes-css-kombinieren/' 
),
'MinifyCSS' => array ( 
'name' => __('Minify CSS', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/css-minimieren/' 
),
'CssJsOrder' => array ( 
'name' => __('Optimize the order of styles and scripts', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/reihenfolge-styles-skripte-optimieren/' 
),
'ParallelDl' => array ( 
'name' => __('Parallelize downloads across hostnames', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/downloads-von-verschiedenen-hostnamen-parallelisieren/' 
),
'NoCookie' => array ( 
'name' => __('Serve static content from a cookieless domain', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/statische-inhalte-von-domains-ohne-cookies-liefern/' 
),
'BadReqs' => array ( 
'name' => __('Avoid bad requests', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/falsche-anfragen-vermeiden/' 
),
'AvoidCharsetInMetaTag' => array ( 
'name' => __('Avoid a character set in the meta tag', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/zeichensatz-im-meta-tag-vermeiden/' 
),
'CssImport' => array ( 
'name' => __('Avoid CSS @import', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/css-import-vermeiden/' 
),
'AvoidLandingPageRedirects' => array ( 
'name' => __('Avoid landing page redirects', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/weiterleitungen-auf-der-zielseite-vermeiden/' 
),
'EnableKeepAlive' => array ( 
'name' => __('Enable Keep-Alive', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/keep-alive-aktivieren/' 
),
'InlineCSS' => array ( 
'name' => __('Inline small CSS', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/kurze-css-skripte-direkt-im-code-platzieren/' 
),
'InlineJS' => array ( 
'name' => __('Inline small JavaScript', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/kurzes-javascript-direkt-im-code-platzieren/' 
),
'MinifyHTML' => array ( 
'name' => __('Minify HTML', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/html-minimieren/' 
),
'MinReqSize' => array ( 
'name' => __('Minimize request size', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/groesse-der-anfragen-minimieren/' 
),
'RemoveQuery' => array ( 
'name' => __('Remove query strings from static resources', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/versions-angaben-bei-der-einbindung-von-statischen-ressourcen-entfernen/' 
),
'ScaleImgs' => array ( 
'name' => __('Serve scaled images', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/bilder-in-der-passenden-groesse-liefern/' 
),
'CacheValid' => array ( 
'name' => __('Specify a cache validator', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/cache-validator-angeben/' 
),
'VaryAE' => array ( 
'name' => __('Specify a Vary: Accept-Encoding header', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/vary-accept-encoding-header-angeben/' 
),
'CharsetEarly' => array ( 
'name' => __('Specify a character set early', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/zeichensatz-moeglichst-frueh-angeben/' 
),
'Sprite' => array ( 
'name' => __('Combine images using CSS sprites', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/bilder-durch-die-verwendung-von-css-sprites-kombinieren/' 
),
'PreferAsync' => array ( 
'name' => __('Prefer asynchronous resources', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/asynchrone-ressourcen-bevorzugen/' 
),
//GTMetrix general
'page_bytes' => array ( 
'name' => __('Reduce the file size of your site', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/dateigroesse-der-seite-reduzieren/' 
),
'page_elements' => array ( 
'name' => __('Reduce the number of elements/resources on your site', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/reduziere-die-anzahl-der-ressourcenelemente-auf-deiner-seite/' 
),

'pagespeed_score' => array ( 
'name' => __('Improve the page speed score of your site', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/page-speed-score-gesamtrating-der-seite-verbessern/' 
),

'page_load_time' => array ( 
'name' => __('Reduce the page load time of your site', 'hootproof-check'),
  'category' => 'Performance',
  'severity' => '', // abhängig vom Score
  'link' => 'https://hootproof.de/ladezeit-der-seite-reduzieren/' 
)


);