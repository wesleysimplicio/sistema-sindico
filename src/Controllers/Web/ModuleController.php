<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\BookingRepository;
use App\Repositories\CommonAreaRepository;
use App\Repositories\CondominiumRepository;
use App\Repositories\ContractorRepository;
use App\Repositories\DeliveryRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\MessageRepository;
use App\Repositories\NoticeRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PorterNoteRepository;
use App\Repositories\ResidentRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\VisitorRepository;

/**
 * Renders admin list pages backed by repositories.
 * Each module renders templates/modules/list.php with rows + columns config.
 */
final class ModuleController
{
    public function condominios(): void
    {
        $rows = (new CondominiumRepository())->all();
        $this->render('condominios', 'Condominios', 'Cadastro de condominios.', '/api/condominiums', $rows, [
            ['key' => 'name',  'label' => 'Nome'],
            ['key' => 'cnpj',  'label' => 'CNPJ'],
            ['key' => 'city',  'label' => 'Cidade'],
            ['key' => 'state', 'label' => 'UF'],
        ]);
    }

    public function unidades(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new UnitRepository())->listByCondominium($cid) : [];
        $this->render('unidades', 'Unidades', 'Apartamentos e blocos.', '/api/units', $rows, [
            ['key' => 'block',  'label' => 'Bloco'],
            ['key' => 'number', 'label' => 'Numero'],
            ['key' => 'floor',  'label' => 'Andar'],
            ['key' => 'type',   'label' => 'Tipo'],
        ]);
    }

    public function moradores(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new UserRepository())->listByCondominium($cid, 'morador') : [];
        $this->render('moradores', 'Moradores', 'Residentes ativos.', '/api/residents', $rows, [
            ['key' => 'name',        'label' => 'Nome'],
            ['key' => 'email',       'label' => 'Email'],
            ['key' => 'phone',       'label' => 'Telefone'],
            ['key' => 'block',       'label' => 'Bloco'],
            ['key' => 'unit_number', 'label' => 'Unidade'],
        ]);
    }

    public function visitantes(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new VisitorRepository())->listByCondominium($cid) : [];
        $this->render('visitantes', 'Visitantes', 'Visitantes esperados e historico.', '/api/visitors', $rows, [
            ['key' => 'name',        'label' => 'Visitante'],
            ['key' => 'document',    'label' => 'Documento'],
            ['key' => 'host_name',   'label' => 'Anfitriao'],
            ['key' => 'status',      'label' => 'Status'],
            ['key' => 'expected_at', 'label' => 'Previsto', 'format' => 'datetime'],
        ]);
    }

    public function avisos(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new NoticeRepository())->listByCondominium($cid, 100) : [];
        $this->render('avisos', 'Avisos', 'Mural de comunicados oficiais.', '/api/notices', $rows, [
            ['key' => 'title',        'label' => 'Titulo'],
            ['key' => 'category',     'label' => 'Categoria'],
            ['key' => 'pinned',       'label' => 'Fixado'],
            ['key' => 'published_at', 'label' => 'Publicado', 'format' => 'datetime'],
        ]);
    }

    public function documentos(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new DocumentRepository())->listByCondominium($cid) : [];
        $this->render('documentos', 'Documentos', 'Atas, regulamentos e arquivos.', '/api/documents', $rows, [
            ['key' => 'title',      'label' => 'Titulo'],
            ['key' => 'category',   'label' => 'Categoria'],
            ['key' => 'created_at', 'label' => 'Criado em', 'format' => 'datetime'],
        ]);
    }

    public function encomendas(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new DeliveryRepository())->listByCondominium($cid) : [];
        $this->render('encomendas', 'Encomendas', 'Recebimento e retirada na portaria.', '/api/deliveries', $rows, [
            ['key' => 'sender',        'label' => 'Remetente'],
            ['key' => 'courier',       'label' => 'Transportadora'],
            ['key' => 'resident_name', 'label' => 'Destinatario'],
            ['key' => 'block',         'label' => 'Bloco'],
            ['key' => 'unit_number',   'label' => 'Unidade'],
            ['key' => 'status',        'label' => 'Status'],
            ['key' => 'received_at',   'label' => 'Recebida em', 'format' => 'datetime'],
        ]);
    }

    public function manutencao(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new MaintenanceRepository())->listByCondominium($cid) : [];
        $this->render('manutencao', 'Manutencao', 'Chamados de manutencao.', '/api/maintenance', $rows, [
            ['key' => 'title',          'label' => 'Titulo'],
            ['key' => 'priority',       'label' => 'Prioridade'],
            ['key' => 'status',         'label' => 'Status'],
            ['key' => 'requester_name', 'label' => 'Solicitante'],
            ['key' => 'created_at',     'label' => 'Aberto em', 'format' => 'datetime'],
        ]);
    }

    public function pagamentos(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new PaymentRepository())->listByCondominium($cid) : [];
        $this->render('pagamentos', 'Pagamentos', 'Boletos e inadimplencia.', '/api/payments', $rows, [
            ['key' => 'description',   'label' => 'Descricao'],
            ['key' => 'resident_name', 'label' => 'Morador'],
            ['key' => 'amount',        'label' => 'Valor', 'format' => 'money'],
            ['key' => 'status',        'label' => 'Status'],
            ['key' => 'due_date',      'label' => 'Vencimento', 'format' => 'date'],
        ]);
    }

    public function reservas(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new BookingRepository())->listByCondominium($cid) : [];
        $this->render('reservas', 'Reservas', 'Reservas de areas comuns.', '/api/bookings', $rows, [
            ['key' => 'area_name',     'label' => 'Area'],
            ['key' => 'resident_name', 'label' => 'Morador'],
            ['key' => 'starts_at',     'label' => 'Inicio', 'format' => 'datetime'],
            ['key' => 'ends_at',       'label' => 'Fim',    'format' => 'datetime'],
            ['key' => 'status',        'label' => 'Status'],
        ]);
    }

    public function areas(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new CommonAreaRepository())->listByCondominium($cid) : [];
        $this->render('areas', 'Areas comuns', 'Salao de festas, churrasqueira, piscina, etc.', '/api/common-areas', $rows, [
            ['key' => 'name',              'label' => 'Nome'],
            ['key' => 'capacity',          'label' => 'Capacidade'],
            ['key' => 'requires_approval', 'label' => 'Aprovacao'],
            ['key' => 'fee',               'label' => 'Taxa', 'format' => 'money'],
        ]);
    }

    public function mensagens(): void
    {
        $cid  = Auth::condominiumId();
        $rows = $cid ? (new MessageRepository())->listByCondominium($cid) : [];
        $this->render('mensagens', 'Mensagens', 'Comunicacao com sindico, portaria e suporte.', '/api/messages', $rows, [
            ['key' => 'channel',    'label' => 'Canal'],
            ['key' => 'subject',    'label' => 'Assunto'],
            ['key' => 'from_name',  'label' => 'De'],
            ['key' => 'to_name',    'label' => 'Para'],
            ['key' => 'created_at', 'label' => 'Enviada em', 'format' => 'datetime'],
        ]);
    }

    public function unitHub(array $params): void
    {
        $unitId = (int) ($params['id'] ?? 0);
        $cid = Auth::condominiumId();
        $unit = $unitId > 0 ? (new UnitRepository())->find($unitId) : null;
        if ($unit === null) {
            http_response_code(404);
            View::render('modules/placeholder', [
                'title'       => 'Unidade nao encontrada | Sistema Sindico',
                'active'      => 'unidades',
                'moduleTitle' => 'Unidade nao encontrada',
                'description' => 'A unidade solicitada nao existe ou nao pertence ao condominio atual.',
            ]);
            return;
        }
        $user = Auth::user();
        $isAdmin = $user !== null && ($user['role'] ?? null) === 'admin';
        if (!$isAdmin) {
            if ($cid === null || (int) $unit['condominium_id'] !== $cid) {
                http_response_code(403);
                View::render('modules/placeholder', [
                    'title'       => 'Acesso negado | Sistema Sindico',
                    'active'      => 'unidades',
                    'moduleTitle' => 'Acesso negado',
                    'description' => 'Voce nao tem permissao para visualizar esta unidade.',
                ]);
                return;
            }
        }

        $condoId = (int) $unit['condominium_id'];
        $residents   = (new ResidentRepository())->allByUnit($condoId, $unitId);
        $vehicles    = (new VehicleRepository())->allByUnit($condoId, $unitId);
        $contractorRepo = new ContractorRepository();
        $contractorRepo->markExpired($condoId);
        $contractors = $contractorRepo->allByUnit($condoId, $unitId);
        $porterNotes = (new PorterNoteRepository())->lastForUnit($condoId, $unitId, 10);

        View::render('modules/unit-hub', [
            'title'       => 'Unidade ' . ($unit['block'] ?? '') . ' ' . ($unit['number'] ?? '') . ' | Sistema Sindico',
            'active'      => 'unidades',
            'unit'        => $unit,
            'residents'   => $residents,
            'vehicles'    => $vehicles,
            'contractors' => $contractors,
            'porterNotes' => $porterNotes,
        ]);
    }

    public function perfil(): void
    {
        $u     = Auth::user();
        $cid   = Auth::condominiumId();
        $condo = $cid ? (new CondominiumRepository())->find($cid) : null;
        $unit  = ($u && !empty($u['unit_id'])) ? (new UnitRepository())->find((int) $u['unit_id']) : null;

        View::render('modules/perfil', [
            'title'  => 'Perfil | Sistema Sindico',
            'active' => 'perfil',
            'user'   => $u,
            'condo'  => $condo,
            'unit'   => $unit,
        ]);
    }

    /** @param array<int,array<string,mixed>> $rows
     *  @param array<int,array{key:string,label:string,format?:string}> $columns */
    private function render(string $key, string $title, string $description, string $api, array $rows, array $columns): void
    {
        View::render('modules/list', [
            'title'       => $title . ' | Sistema Sindico',
            'active'      => $key,
            'moduleTitle' => $title,
            'description' => $description,
            'apiEndpoint' => $api,
            'rows'        => $rows,
            'columns'     => $columns,
        ]);
    }
}
