<?php

$lang = array(

'prefix' => 'Snippet Prefix<p style="font-weight: normal;">Add a prefix to the snippet names. This will make it easier to identify a snippet when used in a template. For example, if your snippet file name is <code>product.html</code>, it will be saved as snippet:product, and used in the template as <code>{snippet:product}</code>.</p>',
'subfolder_suffix' => 'Sub-folder Suffix<p style="font-weight: normal;">If you organize your snippets into sub-folders you can add a suffix to the folder name. For example, if your snippet file name is in <code>/snippets/default_site/global/product.html</code>, it will be saved as <code>snippet:global_product</code>, and used in the template as <code>{snippet:global_product}</code>.</p><p style="font-weight: normal;">This is also used to suffix your MSM Globally Shared Folder name.</p>',
'hide_menu' => 'Hide <i>Snippets</i> in the Design menu?<p style="font-weight: normal;">If you want to allow Snippet editing only on the file system, you can hide the menu item. <a href="http://devot-ee.com/add-ons/wallace/"><b>This option requires Wallace to work.</b></a></p>',
'enable_cleanup' => 'Enabling Cleanup?<p style="font-weight: normal;">This will delete snippets from the database when they are removed from the filesystem. This means if you rename a file based snippet, or delete the file, it will delete it from the database. If you rename a file it will delete the original and create a new database record.</p>',
'msm_shared_folder' => 'MSM Globally Shared Folder<p style="font-weight: normal;">Snippets placed within this folder will be shared between all MSM sites. For example, <code>/snippets/shared/global/header.html</code> Will be accessible in templates between all sites as <code>{snippet:shared_global_header}</code>.</p>',

// IGNORE
''=>'');