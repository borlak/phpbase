<html>
    <head>
        <title><?=$this->page_title?></title>
        <?=$this->css?>
        <?=$this->javascript?>
    </head>
<body>
<ul id="header-nav">
    <li><a href="/index">Index</a></li>
</ul>

<? if($this->error): ?>
<div style="background:red;width:auto">
    <?=$this->error?>
</div>
<? endif ?>

<? if($this->success): ?>
<div style="background:green;width:auto">
    <?=$this->success?>
</div>
<? endif ?>

