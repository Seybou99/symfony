<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\ByteString;

#[Route('/admin')]
class ProductController extends AbstractController
{
	public function __construct(private ProductRepository $productRepository, private RequestStack $requestStack, private EntityManagerInterface $entityManager)
	{
	}

	#[Route('/product', name: 'admin.product.index')]
	public function index(): Response
	{
		return $this->render('admin/product/index.html.twig', [
			'products' => $this->productRepository->findAll(),
		]);
	}

	#[Route('/product/form', name: 'admin.product.form')]
	#[Route('/product/update/{id}', name: 'admin.product.update')]
	public function form(int $id = null): Response
	{
		// création d'un formulaire
		$entity = $id ? $this->productRepository->find($id) : new Product();
		$type = ProductType::class;

		// conserver le nom de l'image du produit au cas où il n'y a pas de sélection d'image lors de la modification
		$entity->prevImage = $entity->getImage();

		$form = $this->createForm($type, $entity);

		// récupérer la saisie précédente dans la requête http
		$form->handleRequest($this->requestStack->getMainRequest());

		// si le formulaire est valide et soumis
		if ($form->isSubmitted() && $form->isValid()) {
			// gestion de l'image
			// ByteString permet de générer une chaîne de caractères aléatoire
			$filename = ByteString::fromRandom(32)->lower();

			// accéder à la classe UploadedFile à partir de la propriété image de l'entité
			$file = $entity->getImage();

			// si une image a été sélectionnée
			if ($file instanceof UploadedFile) {
				// extension du fichier
				$fileExtension = $file->guessClientExtension();

				// transfert de l'image vers public/img
				$file->move('img', "$filename.$fileExtension");

				// modifier la propriété image de l'entité
				$entity->setImage("$filename.$fileExtension");

				// supprimer l'image précédente
				if ($id) unlink("img/{$entity->prevImage}");
			}
			// si une image n'a pas été sélectionnée
			else {
				// récupérer la valeur de la propriété prevImage
				$entity->setImage($entity->prevImage);
			}

			// dd($filename, $entity);

			// dd($entity);
			// insérer dans la base
			$this->entityManager->persist($entity);
			$this->entityManager->flush();

			// message de confirmation
			$message = $id ? 'Product updated' : 'Product created';

			// message flash : message stocké en session, supprimé suite à son affichage
			$this->addFlash('notice', $message);

			// redirection vers la page d'accueil de l'admin
			return $this->redirectToRoute('admin.product.index');
		}

		return $this->render('admin/product/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	#[Route('/product/delete/{id}', name: 'admin.product.delete')]
	public function delete(int $id): RedirectResponse
	{
		// sélectionner l'entité à supprimer
		$entity = $this->productRepository->find($id);

		// supprimer l'entité
		$this->entityManager->remove($entity);
		$this->entityManager->flush();

		// supprimer l'image
		unlink("img/{$entity->getImage()}");

		// message de confirmation
		$this->addFlash('notice', 'Product deleted');

		// redirection
		return $this->redirectToRoute('admin.product.index');
	}
}
