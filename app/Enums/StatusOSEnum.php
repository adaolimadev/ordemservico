<?php

namespace App\Enums;

enum StatusOSEnum: string
{
    case ABERTA = 'ABERTA';
    case EM_ANALISE = 'EM_ANALISE';
    case EM_EXECUCAO = 'EM_EXECUCAO';
    case AGUARDANDO_CLIENTE = 'AGUARDANDO_CLIENTE';
    case CONCLUIDA = 'CONCLUIDA';
    case CANCELADA = 'CANCELADA';
}