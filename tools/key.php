<?php
if(PHP_SAPI!=='cli') exit;
echo bin2hex(random_bytes(32)).PHP_EOL;
