<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Reservation;
use App\Repository\BookRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/books', name: 'books_', format: 'json')]
final class BookController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(BookRepository $repository): JsonResponse
    {
        $books = $repository->findAll();

        $data = array_map(fn($book) => [
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
        ], $books);

        return $this->json($data);
    }

    #[Route('/{id}/reserve', name: 'reserve', methods: ['POST'])]
    public function reserve(
        Book $book,
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Vérifie s'il y a une réservation active (non expirée) pour ce livre
        $now = new \DateTimeImmutable();

        $existingReservation = $reservationRepository->createQueryBuilder('r')
            ->andWhere('r.book = :book')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('book', $book)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingReservation) {
            return $this->json(['error' => 'Book already reserved'], 400);
        }

        // Récupère l'email utilisateur depuis la requête POST (JSON ou formulaire)
        $data = json_decode($request->getContent(), true);
        $userEmail = $data['userEmail'] ?? null;

        if (!$userEmail) {
            return $this->json(['error' => 'User email is required'], 400);
        }

        // Crée la réservation
        $reservation = new Reservation();
        $reservation->setBook($book);
        $reservation->setUserEmail($userEmail);
        $reservation->setReservedAt(new \DateTime());
        $reservation->setExpiresAt($now->modify('+1 day')); // expiration dans 1 jour par ex.

        $em->persist($reservation);
        $em->flush();

        return $this->json(['message' => 'Book reserved successfully']);
    }
}
