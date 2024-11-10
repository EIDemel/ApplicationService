<?php
// src/Controller/BookingController.php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Service;
use App\Form\BookingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    private $entityManager;

    // Injecter l'EntityManagerInterface via le constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/booking/new', name: 'booking_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $serviceId = $request->query->get('service_id');

        $service = $this->entityManager->getRepository(Service::class)->find($serviceId);

        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé.');
        }

        $booking = new Booking();
        $booking->setService($service);
        $form = $this->createForm(BookingType::class, $booking);

        // Traiter la soumission du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer la date et l'heure de la réservation
            $bookingDate = $booking->getDate();

            // Vérifier s'il y a déjà une réservation pour ce service à cette date/heure
            $existingBooking = $this->entityManager->getRepository(Booking::class)->findOneBy([
                'service' => $service,
                'date' => $bookingDate,
            ]);

            if ($existingBooking) {
                // Si une réservation existe déjà, afficher un message d'erreur
                $this->addFlash('error', 'Ce créneau est déjà réservé pour ce service.');

                // Renvoyer l'utilisateur au formulaire avec les erreurs
                return $this->render('booking/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Si la réservation est disponible, enregistrer la réservation
            $entityManager->persist($booking);
            $entityManager->flush();

            // Message de confirmation
            $this->addFlash('success', 'Votre réservation a été enregistrée !');

            return $this->redirectToRoute('booking_success'); // Redirection vers une page de confirmation
        }

        return $this->render('booking/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/booking/success', name: 'booking_success')]
    public function success(): Response
    {
        return $this->render('booking/success.html.twig');
    }
}
