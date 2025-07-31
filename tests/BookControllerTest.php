<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookControllerTest extends WebTestCase
{
    // Ajout de cette méthode pour indiquer la classe Kernel utilisée
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    public function testListBooks()
    {
        // Crée un client HTTP pour faire les requêtes
        $client = static::createClient();

        // Envoie une requête GET sur la route /api/books
        $client->request('GET', '/api/books');

        // Vérifie que la réponse a le statut HTTP 200 (OK)
        $this->assertResponseIsSuccessful();

        // Vérifie que le contenu de la réponse est bien du JSON
        $this->assertResponseHeaderSame('content-type', 'application/json');

        // Récupère le contenu JSON décodé
        $data = json_decode($client->getResponse()->getContent(), true);

        // Vérifie que les données reçues sont bien un tableau
        $this->assertIsArray($data);

        // (optionnel) Vérifie que chaque livre contient bien les clés attendues
        if (!empty($data)) {
            $this->assertArrayHasKey('id', $data[0]);
            $this->assertArrayHasKey('title', $data[0]);
            $this->assertArrayHasKey('author', $data[0]);
        }
    }

    public function testReserveBook()
    {
        $client = static::createClient();

        // TODO : récupérer un livre existant dans la base (via repository)
        // ou créer un livre de test

        // Nettoyer les réservations existantes pour ce livre (pour un test propre)

        // Teste une réservation réussie :
        // Envoie une requête POST sur /api/books/{id}/reserve
        // avec un corps JSON contenant un "userEmail" valide
        $client->request('POST', '/api/books/1/reserve', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'userEmail' => 'test@example.com'
        ]));

        // Vérifie que la réponse est un succès (ex: 200)
        $this->assertResponseIsSuccessful();

        // Vérifie le message de confirmation dans la réponse JSON
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Book reserved successfully', $responseData['message'] ?? '');

        // Teste le cas d'erreur quand le livre est déjà réservé :
        // Refait une requête POST avec la même réservation
        $client->request('POST', '/api/books/1/reserve', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'userEmail' => 'another@example.com'
        ]));

        // Vérifie que la réponse est une erreur (ex: 400)
        $this->assertResponseStatusCodeSame(400);

        // Vérifie que le message d'erreur est bien celui attendu
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Book already reserved', $responseData['error'] ?? '');
    }
}
