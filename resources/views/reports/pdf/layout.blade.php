<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>@yield('title') - Tiba Multi-Services</title>
<style>
    @page { margin: 1.9cm 1.5cm 1.9cm 1.5cm; }
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { font-size: 10pt; line-height: 1.45; color: #1f2d36; margin: 0; }
    h1 { font-size: 17pt; color: #38434a; margin: 0; }
    h2 { font-size: 13.5pt; color: #2C4A5E; margin: 32px 0 13px; padding-bottom: 5px; border-bottom: 2px solid #7CB342; }
    h3 { font-size: 11pt; color: #2C4A5E; margin: 22px 0 6px; }
    .muted { color: #6b7a84; }
    .right { text-align: right; }
    .center { text-align: center; }
    .nowrap { white-space: nowrap; }
    .bold { font-weight: bold; }
    .marge { margin: 30px 0 10px; padding-bottom: 5px; }

    table { width: 100%; border-collapse: collapse; }

    /* En-tête : respiration avant et après le filet bleu */
    .head td { vertical-align: middle; padding: 0 0 14px 0; }
    .logo { height: 52px; }
    .rule { height: 0; border-top: 3px solid #2C4A5E; margin: 0 0 24px 0; }
    .head-meta { font-size: 8.5pt; color: #6b7a84; line-height: 1.5; }

    /* Tableaux */
    .data { font-size: 9pt; }
    .data th { background: #2C4A5E; color: #fff; padding: 7px 7px; font-size: 8.5pt; text-align: left; border: 1px solid #2C4A5E; }
    .data th.right { text-align: right; }
    .data td { padding: 6px 7px; border: 1px solid #d3dce0; vertical-align: top; }
    .data tr.alt td { background: #f5f8f9; }
    .data tfoot td, .data tr.total td { background: #eaf3df; font-weight: bold; border: 1px solid #b9d68f; }
    .data tr.cancelled td { color: #9aa5ab; text-decoration: line-through; }
    .sub { font-size: 7.5pt; color: #6b7a84; }

    /* Cartes d'indicateurs : bord coloré, fond très pâle, coins arrondis */
    .kpis { border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; width: 103%; }
    .kpis td { padding: 0; vertical-align: top; width: 25%; }
    .kpi { border: 1.5px solid; border-radius: 10px; padding: 11px 12px; }
    .kpi .label { font-size: 7.5pt; color: #4d5d67; }
    .kpi .value { font-size: 11.5pt; font-weight: bold; color: #1f2d36; margin-top: 5px; }
    .kpi .extra { font-size: 7.5pt; color: #6b7a84; margin-top: 2px; }
    .k1 { background: #eaf5f4; border-color: #1a7f7a; }
    .k2 { background: #fff7e3; border-color: #d9a23a; }
    .k3 { background: #f3f0f8; border-color: #7d6a9a; }
    .k4 { background: #eef3f8; border-color: #2C4A5E; }

    .tag { font-size: 8pt; font-weight: bold; }
    .ok { color: #4f8a10; }
    .warn { color: #b7791f; }
    .bad { color: #c0392b; }
    .empty { padding: 14px; text-align: center; color: #6b7a84; border: 1px dashed #cfd8dc; border-radius: 6px; }
    .note { font-size: 8pt; color: #6b7a84; margin-top: 8px; }

    /*Revele de compte client  */
    /* Lisibilité : plus de gris clair ni de texte minuscule pour les infos utiles */
    .muted { color: #44525b; }
    .note  { font-size: 9pt; color: #2f3d46; }
    .sub   { font-size: 8pt; color: #44525b; }
    .kpi .label { font-size: 8pt; color: #3a4850; }

    /* Badge « En cours » (fiche d'une dette) */
    .pill { font-size: 8pt; font-weight: bold; padding: 3px 10px; border-radius: 10px; border: 1px solid; }
    .st-active { color: #3e7d0a; background: #eef6e3; border-color: #7CB342; }

    /* Statuts de l'historique : couleur du texte seulement, sans fond */
    .stt { font-size: 8.5pt; font-weight: bold; }
    .t-soldee     { color: #1d5f8a; }
    .t-abandonnee { color: #b36b00; }
    .t-annulee    { color: #6b7a84; }

    /* Fiche d'une dette en cours : petits blocs discrets */
    .figs { border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 10px; width: 102%; }
    .figs td { padding: 0; vertical-align: top; width: 33%; }
    .fig { border: 1px solid; border-radius: 8px; padding: 6px 10px; }
    .fig-label { font-size: 7pt; color: #3a4850; }
    .fig-value { font-size: 9.5pt; font-weight: bold; color: #1f2d36; margin-top: 2px; }
    .fig-value.big { font-size: 9.5pt; }
    .f-due   { background: #fff7e3; border-color: #d9a23a; }
    .f-paid  { background: #eaf5f4; border-color: #1a7f7a; }
    .f-init  { background: #f5f7f8; border-color: #b8c3c9; }

    .uptodate { border: 1.5px solid #7CB342; background: #eef6e3; border-radius: 10px; padding: 12px 16px; font-size: 10.5pt; color: #1f2d36; }

    .debt-block { margin-bottom: 22px; page-break-inside: avoid; }
    .debt-head { width: 100%; margin-bottom: 8px; margin-top: 10px; }
    .debt-head td { padding: 0; }
    .debt-title { font-size: 12.7pt; font-weight: bold; color: #1f2d36; }
    .debt-date  { font-size: 8.7pt; color: #44525b; }
    .legend { font-size: 9pt; color: #2f3d46; margin-top: 10px; line-height: 1.5; }

</style>
</head>
<body>

<script type="text/php">
if (isset($pdf)) {
    $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
    $pdf->page_text($pdf->get_width() - 110, $pdf->get_height() - 36, "Page {PAGE_NUM} / {PAGE_COUNT}", $font, 8.5, [0.42, 0.48, 0.52]);
    $pdf->page_text(54, $pdf->get_height() - 36, "Tiba Multi-Services — Document confidentiel", $font, 8.5, [0.42, 0.48, 0.52]);
}
</script>

<table class="head">
    <tr>
        <td style="width: 70px;">
            @if($logo)<img class="logo" src="{{ $logo }}" alt="Tiba">@endif
        </td>
        <td>
            <h1>@yield('title')</h1>
            <div class="muted" style="margin-top:4px; font-size:10pt;">@yield('subtitle')</div>
        </td>
        <td class="right head-meta">
            <b>Tiba Multi-Services</b><br>
            Généré le {{ $generatedAt->format('d/m/Y à H:i') }}<br>
            par {{ $generatedBy }}
        </td>
    </tr>
</table>
<div class="rule"></div>

@yield('content')

</body>
</html>
