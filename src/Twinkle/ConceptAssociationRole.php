<?php

namespace Sifrious\Elwin\Twinkle;

enum ConceptAssociationRole: string
{
    case About = 'about';
    case Applies = 'applies';
    case Questions = 'questions';
    case Contrasts = 'contrasts';
    case InspiredBy = 'inspired-by';
}
