<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\ByteString;


class ContactController extends AbstractController
{
	public function __construct(private ContactRepository $contactRepository, private RequestStack $requestStack, private EntityManagerInterface $entityManager)
	{
	}

	#[Route('/contact', name: 'contact.index')]
	public function index(): Response
	{
		return $this->render('contact/index.html.twig', [
			'contacts' => $this->contactRepository->findAll(),
		]);
	}

	#[Route('/contact/form', name: 'contact.form')]
	public function form(int $id = null): Response
	{
		// création d'un formulaire
		$entity = $id ? $this->contactRepository->find($id) : new Contact();
		$type = ContactType::class;

		// conserver le nom de l'image du produit au cas où il n'y a pas de sélection d'image lors de la modification

		$form = $this->createForm($type, $entity);

		// récupérer la saisie précédente dans la requête http
		$form->handleRequest($this->requestStack->getMainRequest());

		// si le formulaire est valide et soumis
		if ($form->isSubmitted() && $form->isValid()) {
			// gestion de l'image
			// ByteString permet de générer une chaîne de caractères aléatoire
			// accéder à la classe UploadedFile à partir de la propriété image de l'entité

			// si une image a été sélectionnée
		

			// dd($filename, $entity);

			// dd($entity);
			// insérer dans la base
			$this->entityManager->persist($entity);
			$this->entityManager->flush();

			// message de confirmation
			$message = $id ? 'Contact updated' : 'Contact created';

			// message flash : message stocké en session, supprimé suite à son affichage
			$this->addFlash('notice', $message);

			// redirection vers la page d'accueil de l'admin
			return $this->redirectToRoute('contact.form');
		}

		return $this->render('contact/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	
}
