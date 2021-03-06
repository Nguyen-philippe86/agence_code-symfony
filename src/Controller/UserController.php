<?php

namespace App\Controller;

use App\Entity\Property;
use App\Entity\User;
use App\Form\EditProfileType;
use App\Form\PropertyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class UserController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/user", name="user")
     */
    public function profil()
    {
        return $this->render('user/user.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    /**
     * @Route("/user/{id}/edit", name="user_profil_edit")
     */
    public function editProfile(User $user = null, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        $form = $this->createForm(EditProfileType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour');

            return $this->redirectToRoute('user');
        }

        return $this->render('user/edit-profil.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/user/property/create", name="user_create")
     * @Route("/user/property/{id}", name="user_edit", methods="GET|POST")
     */
    public function createAndEdit(Property $property = null, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$property) { // On vérifie si ces un ajout ou une modification
            $property = new Property();
        }
        $form = $this->createForm(PropertyType::class, $property);

        $form->handleRequest($request); // Analyse les infos de la request si le form a été soumis
        if ($form->isSubmitted() && $form->isValid()) {
            $property->setCreatedAt(new \DateTime()); // On donne une info supplémentaire (sa date de création) au formulaire
            $modif = null !== $property->getId(); // Si dans l"annonce, il y a déjà un ID, alors sa sera une modif
            $user = $this->security->getUser();
            $property->setAuthor($user); // On donne une info supplémentaire (son auteur) au formulaire
            $entityManager->persist($property);
            $entityManager->flush();
            $this->addFlash('success', ($modif) ? 'La modification a été effectué' : "L'ajout a été effectué");

            return $this->redirectToRoute('user'); // Redirige vers la page "annonce"
        }

        return $this->render('user/create.html.twig', [
            'property' => $property,
            'form' => $form->createView(),
            'isModification' => null !== $property->getid(),
            // Si isModification est = null, alors on sera sur modification, sinon l'inverse
        ]);
    }

    /**
     * @Route("/user/property/{id}", name="user_delete", methods="delete")
     */
    public function delete(Property $property, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('SUP'.$property->getId(), $request->get('_token'))) {
            $entityManager->remove($property);
            $entityManager->flush();
            $this->addFlash('success', 'La suppression a été effectué');

            return $this->redirectToRoute('user');
        }
    }
}
