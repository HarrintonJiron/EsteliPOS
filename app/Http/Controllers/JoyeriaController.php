<?php

namespace App\Http\Controllers;

class JoyeriaController extends ReparacionController
{
    protected function workshopType(): string
    {
        return 'jewelry';
    }

    protected function workshopRoutePrefix(): string
    {
        return 'joyeria';
    }
}
