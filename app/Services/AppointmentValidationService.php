<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientPet;
use App\Models\StaffMember;
use App\Models\BranchStaff;
use App\Models\BranchServiceVariant;
use App\Models\BranchServiceVariantStaff;
use App\Models\Organization;
use App\Models\Branch;


class AppointmentValidationService
{

    public function validatePublicCreate()
    {
        // FALTAN VALIDACIONES PARA BOOKING-PUBLIC
    }

    public function validateAdminReschedule()
    {
        // FALTAN vallidaciones para cuando se reagende tenemos que ver si se va a necesitar otro metodo para booking-public
    }

    public function validateAdminCreate(
        Organization $organization,
        Branch $branch,
        array $data
    ): array {

        $client = $this->validateClient(
            $organization,
            $data['client_id']
        );

        $staff = $this->validateStaff(
            $organization,
            $branch,
            $data['staff_member_id']
        );

        $variant = $this->validateVariant(
            $organization,
            $branch,
            $data['branch_service_variant_id']
        );

        $this->validateStaffCanProvideVariant(
            $staff,
            $variant
        );

        $pet = $this->validatePetGrooming(
            $organization,
            $client,
            $data
        );

        return [
            'client' => $client,
            'staff' => $staff,
            'variant' => $variant,
            'pet' => $pet,
        ];
    }

    private function validateClient(
        Organization $organization,
        int $clientId
    ): Client {

        $client = Client::query()
            ->where('id', $clientId)
            ->where('organization_id', $organization->id)
            ->first();

        if (!$client) {
            abort(404, 'Cliente no encontrado');
        }

        return $client;
    }

    private function validateStaff(
        Organization $organization,
        Branch $branch,
        int $staffId
    ): StaffMember {

        $staff = StaffMember::query()
            ->where('id', $staffId)
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->first();

        if (!$staff) {
            abort(
                404,
                'Profesional no encontrado o desactivado'
            );
        }

        $belongsToBranch = BranchStaff::query()
            ->where('branch_id', $branch->id)
            ->where('staff_member_id', $staff->id)
            ->exists();

        if (!$belongsToBranch) {
            abort(
                422,
                'El profesional no pertenece a esta sucursal'
            );
        }

        return $staff;
    }

    private function validateVariant(
        Organization $organization,
        Branch $branch,
        int $variantId
    ): BranchServiceVariant {

        $variant = BranchServiceVariant::query()
            ->where('id', $variantId)
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->where('active', true)
            ->first();

        if (!$variant) {
            abort(
                422,
                'El servicio no pertenece a esta sucursal'
            );
        }

        return $variant;
    }

    private function validateStaffCanProvideVariant(
        StaffMember $staff,
        BranchServiceVariant $variant
    ): void {

        $exists =
            BranchServiceVariantStaff::query()
            ->where(
                'branch_service_variant_id',
                $variant->id
            )
            ->where(
                'staff_member_id',
                $staff->id
            )
            ->where(
                'active',
                true
            )
            ->exists();

        if (!$exists) {
            abort(
                422,
                'El profesional no tiene asignado este servicio'
            );
        }
    }

    private function validatePetGrooming(
        Organization $organization,
        Client $client,
        array $data
    ): ?ClientPet {

        if (
            $organization->business_niche !== 'pet_grooming'
        ) {
            return null;
        }

        if (empty($data['pet_id'])) {
            abort(
                422,
                'Debes seleccionar una mascota.'
            );
        }

        $pet = ClientPet::query()
            ->where('id', $data['pet_id'])
            ->where('organization_id', $organization->id)
            ->first();

        if (!$pet) {
            abort(
                422,
                'Mascota no encontrada.'
            );
        }

        if ($pet->client_id !== $client->id) {
            abort(
                422,
                'La mascota no pertenece al cliente seleccionado.'
            );
        }

        return $pet;
        
    }
}
