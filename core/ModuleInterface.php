<?php

namespace KitchenManager\Core;

interface ModuleInterface 
{
    public function getId(): string;
    public function init(): void;
}