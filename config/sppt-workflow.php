<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pembiayaan scheme → workflow code (wf_workflow_name.wfa_workflow_code)
    |--------------------------------------------------------------------------
    */
    'scheme_workflows' => [
        'TEKUN Niaga' => 'TEKUN_NIAGA',
        'Tekun Niaga' => 'TEKUN_NIAGA',
    ],

    'default_workflow_code' => 'TEKUN_NIAGA',

    /** Extended-field keys stored on wf_process / wf_process_details JSON columns. */
    'extended_keys' => [
        'process_stage' => 'permohonan_stage',
        'process_status' => 'permohonan_status',
        'process_permission' => 'permohonan_permission',
        'process_route' => 'permohonan_route',
        'detail_status' => 'permohonan_status',
        'detail_keputusan' => 'keputusan',
    ],
];
