<?php
namespace App\Traits;

trait VerificarPermissao
{
    public function contem($idCompany, $user, $permissao)
    {

        try {

            $result = $user->groups->filter(function($item) use ($idCompany) {
                return $item['company_id'] == $idCompany;
            });

            $filteredItems = $result[0]->items->filter(function($item) use ($permissao) {
                return $item['slug'] == $permissao;
            });

            return (count($filteredItems) > 0);

        } catch (\Exception $e) {
            return false;
        }



    }
}
