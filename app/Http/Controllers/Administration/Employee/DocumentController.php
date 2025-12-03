<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class DocumentController extends Controller
{
    /**
     * Actualiza observaciones y estatus del documento.
     * Espera: doc_id, observaciones (string), estatus ("validado"/"aceptar"/"aceptado" -> 2, "rechazado"/"rechazar" -> 1).
     */
public function updateStatus(Request $request)
{
    $request->validate([
        'doc_id'        => 'required|integer|min:1',
        'observaciones' => 'nullable|string',
        'estatus'       => 'required|string',
        // 🔒 Concurrencia:
        'if_match'      => 'required|string',     // ISO8601 del actualizado_en que vio el usuario
        'hist_seen'     => 'nullable|integer',    // versión de historial que vio (conteo)
    ]);

    $docId      = (int) $request->input('doc_id');
    $obs        = (string) ($request->input('observaciones') ?? '');
    $estatus    = strtolower(trim($request->input('estatus')));
    $ifMatchIso = $request->input('if_match');
    $histSeen   = (int) ($request->input('hist_seen') ?? 0);

    // Mapa estatus -> id_cat_estatus_documento
    $estatusMap = [
        'validado'  => 2,
        'aceptar'   => 2,
        'aceptado'  => 2,
        'rechazado' => 1,
        'rechazar'  => 1,
    ];
    if (!array_key_exists($estatus, $estatusMap)) {
        return response()->json([
            'status'  => false,
            'message' => 'Estatus inválido.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    $idEstatus = $estatusMap[$estatus];

    $userId = Auth::id();
    $now    = Carbon::now();

    // Traer estado actual
    $doc = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
        ->where('id_ctrl_documentos_profesionalizacion', $docId)
        ->first();

    if (!$doc) {
        return response()->json([
            'status'  => false,
            'message' => 'Documento no encontrado.',
        ], Response::HTTP_NOT_FOUND);
    }

    // Versión/ETag actual
    $currentStamp = $doc->actualizado_en ?: $doc->creado_en;
    $currentIso   = $currentStamp ? Carbon::parse($currentStamp)->toIso8601String() : null;

    // 🔒 1) Precondition: lo que el usuario vio debe coincidir con lo actual
    if (!$currentIso || $currentIso !== $ifMatchIso) {
        return response()->json([
            'status'  => false,
            'message' => 'El documento ya fue procesado en otra ventana. Actualiza la página antes de continuar.',
            'code'    => 'PRECONDITION_FAILED',
        ], 409);
    }

    // Contar historial actual
    $histCount = DB::table('profesionalizacion.ctrl_historia_documentos')
        ->where('id_ctrl_documentos_profesionalizacion', $docId)
        ->count();

    // 🔒 2) Si la tabla ya tiene más historial del que vio el usuario, bloquear
    if ($histCount > $histSeen) {
        return response()->json([
            'status'  => false,
            'message' => 'Este documento cambió recientemente. Actualiza la página para ver los cambios.',
            'code'    => 'STALE_VIEW',
        ], 409);
    }

    // 🔒 3) Si el MISMO usuario ya autorizó/rechazó después de lo que vio, bloquear
    $ifMatchTs = Carbon::parse($ifMatchIso);
    $alreadyByMe = DB::table('profesionalizacion.ctrl_historia_documentos')
        ->where('id_ctrl_documentos_profesionalizacion', $docId)
        ->where('id_usuario_autorizacion', $userId)
        ->where('creado_en', '>', $ifMatchTs)
        ->exists();

    if ($alreadyByMe) {
        return response()->json([
            'status'  => false,
            'message' => 'Ya procesaste este documento en otra ventana. Actualiza la página.',
            'code'    => 'DUPLICATE_BY_SAME_USER',
        ], 409);
    }

    DB::beginTransaction();
    try {
        // 🔒 4) Update condicionado por ETag: solo si no cambió “actualizado_en”
        $affected = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
            ->where('id_ctrl_documentos_profesionalizacion', $docId)
            ->where(function($q) use ($currentStamp) {
                if ($currentStamp) $q->where('actualizado_en', '=', $currentStamp);
                else $q->whereNull('actualizado_en');
            })
            ->update([
                'observaciones'            => $obs,
                'id_cat_estatus_documento' => $idEstatus,
                'actualizado_en'           => $now,
                'fecha_autorizacion'       => $now,
                'id_usuario_actualizacion' => $userId,
                'id_usuario_autorizacion'  => $userId,
            ]);

        if ($affected !== 1) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Conflicto de concurrencia. El documento cambió mientras lo editabas.',
                'code'    => 'WRITE_CONFLICT',
            ], 409);
        }

        DB::table('profesionalizacion.ctrl_historia_documentos')->insert([
            'id_ctrl_documentos_profesionalizacion' => $docId,
            'observaciones'                         => $obs,
            'id_cat_estatus_documento'              => $idEstatus,
            'creado_en'                              => $now,
            'actualizado_en'                         => $now,
            'fecha_autorizacion'                     => $now,
            'id_usuario_creacion'                    => $userId,
            'id_usuario_actualizacion'               => $userId,
            'id_usuario_autorizacion'                => $userId,
        ]);

        DB::commit();

        // Nuevo ETag/versión para el front
        $newEtag = $now->toIso8601String();
        $newHist = $histCount + 1;

        return response()->json([
            'status' => true,
            'message'=> 'Documento actualizado y registrado en el historial.',
            'result' => [
                'doc_id'       => $docId,
                'estatus_id'   => $idEstatus,
                'new_etag'     => $newEtag,
                'hist_version' => $newHist,
            ],
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'status'  => false,
            'message' => 'No fue posible actualizar y registrar el historial.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
}
