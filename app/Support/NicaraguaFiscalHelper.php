<?php

namespace App\Support;

class NicaraguaFiscalHelper
{
    /**
     * Tasa general del Impuesto al Valor Agregado (IVA) en Nicaragua
     * según el Art. 109 de la Ley No. 822 (Ley de Concertación Tributaria).
     */
    public const GENERAL_IVA_RATE = 15.0;

    /**
     * Regímenes tributarios soportados para Nicaragua (DGI - Ley No. 822).
     */
    public const REGIME_GENERAL = 'regimen_general';
    public const REGIME_CUOTA_FIJA = 'cuota_fija';
    public const REGIME_EXENTO = 'exento';

    /**
     * Plazo mínimo legal de conservación de documentos de interés tributario (años)
     * según los Arts. 43 y 103 numeral 3 del Código Tributario de Nicaragua (Ley No. 562).
     */
    public const MIN_DOCUMENT_RETENTION_YEARS = 4;

    /**
     * Devuelve el catálogo de regímenes tributarios configurables.
     *
     * @return array<string, string>
     */
    public static function regimes(): array
    {
        return [
            self::REGIME_GENERAL => 'Régimen General (Responsable Recaudador IVA - 15% Ley 822)',
            self::REGIME_CUOTA_FIJA => 'Régimen Simplificado de Cuota Fija (No traslada IVA - Art. 251 Ley 822)',
            self::REGIME_EXENTO => 'Actividades / Entidades Exentas de IVA (0%)',
        ];
    }

    /**
     * Determina si una cadena cumple con el formato de Cédula de Identidad Ciudadana de Nicaragua
     * (13 dígitos + 1 letra final, con o sin guiones: ej. 001-010190-0001A o 0010101900001A).
     */
    public static function isValidNicaraguaCedula(string $value): bool
    {
        $normalized = strtoupper(preg_replace('/[\s\-]/', '', trim($value)));

        return (bool) preg_match('/^\d{13}[A-Z]$/', $normalized);
    }

    /**
     * Determina si una cadena cumple con el formato de Número RUC de la DGI Nicaragua
     * (14 caracteres alfanuméricos: Personas Jurídicas J + 13 dígitos, Personas Naturales con RUC 13 dígitos + letra, o N/E/R + 13 caracteres).
     */
    public static function isValidNicaraguaRuc(string $value): bool
    {
        $normalized = strtoupper(preg_replace('/[\s\-]/', '', trim($value)));

        return (bool) preg_match('/^([JNER]\d{13}|\d{13}[A-Z]|\d{14})$/', $normalized);
    }

    /**
     * Normaliza un RUC o Cédula eliminando espacios laterales y convirtiendo letras a mayúsculas.
     */
    public static function normalizeDocument(string $value): string
    {
        return strtoupper(trim($value));
    }
}
