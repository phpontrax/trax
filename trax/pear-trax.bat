@echo off
REM  Copy directory Pear data_dir/PHPonTrax/data/
REM
REM  (PHP 5)
REM
REM  This is the Windows version of the console command that creates
REM  the skeleton directories for a PHPonTrax application
REM  $Id$
"@php_bin@" "@php_dir@/PHPonTrax/trax.php" %*
