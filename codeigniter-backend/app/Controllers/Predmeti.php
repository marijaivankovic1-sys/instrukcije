<?php

namespace App\Controllers;

use App\Models\PredmetModel;
use CodeIgniter\RESTful\ResourceController;

class Predmeti extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $model = new PredmetModel();

        return $this->respond([
            'uspjeh' => true,
            'predmeti' => $model->findAll()
        ]);
    }

    public function show($id = null)
    {
        $model = new PredmetModel();

        $predmet = $model->find($id);

        if (!$predmet) {
            return $this->failNotFound('Predmet nije pronađen.');
        }

        return $this->respond([
            'uspjeh' => true,
            'predmet' => $predmet
        ]);
    }

    public function create()
    {
        $model = new PredmetModel();

        $data = $this->request->getJSON(true);

        if (empty($data['naziv'])) {
            return $this->failValidationErrors('Naziv predmeta je obavezan.');
        }

        $noviPredmet = [
            'naziv' => trim($data['naziv']),
            'opis' => isset($data['opis']) ? trim($data['opis']) : ''
        ];

        $id = $model->insert($noviPredmet, true);

        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' => 'Predmet je uspješno dodan.',
            'id' => $id
        ]);
    }

    public function update($id = null)
    {
        $model = new PredmetModel();

        $predmet = $model->find($id);

        if (!$predmet) {
            return $this->failNotFound('Predmet nije pronađen.');
        }

        $data = $this->request->getJSON(true);

        if (empty($data['naziv'])) {
            return $this->failValidationErrors('Naziv predmeta je obavezan.');
        }

        $podaci = [
            'naziv' => trim($data['naziv']),
            'opis' => isset($data['opis']) ? trim($data['opis']) : ''
        ];

        $model->update($id, $podaci);

        return $this->respond([
            'uspjeh' => true,
            'poruka' => 'Predmet je uspješno uređen.'
        ]);
    }

    public function delete($id = null)
    {
        $model = new PredmetModel();

        $predmet = $model->find($id);

        if (!$predmet) {
            return $this->failNotFound('Predmet nije pronađen.');
        }

        $model->delete($id);

        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' => 'Predmet je uspješno obrisan.'
        ]);
    }
}