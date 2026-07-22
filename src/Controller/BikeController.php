<?php

namespace App\Controller;
use App\Service\BikeRepository;
use App\Form\BikeType;
use App\Model\Bike;
use App\Model\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class BikeController extends AbstractController
{
    #[Route('/', name: 'bike_list')]
    public function list(Request $request, BikeRepository $repository): Response
    {
        $category = Category::tryFrom((string) $request->query->get('category', ''));
        $sort = $request->query->get('sort');
        $query = trim((string) $request->query->get('q', ''));
        $searchQuery = $query !== '' ? $query : null;

        return $this->render('bike/list.html.twig', [
            'bikes' => $repository->all($category, $sort, $searchQuery),
            'categories' => Category::cases(),
            'activeCategory' => $category,
            'sort' => $sort,
            'query' => $searchQuery,
        ]);
    }

    // IMPORTANT: declared before /bikes/{id}, otherwise "new" would
    // be captured by the {id} wildcard.
    #[Route('/bikes/new', name: 'bike_new')]
    public function new(Request $request, BikeRepository $repository): Response
    {
        $form = $this->createForm(BikeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $bike = new Bike(
                id: $repository->nextId($data['category']),
                brand: trim($data['brand']),
                model: trim($data['model']),
                category: $data['category'],
                driveType: $data['driveType'],
                batteryWh: $data['battery'],
                frameSize: trim($data['frameSize']),
                mileageKm: $data['mileage'],
                originalPriceCents: $data['originalPrice'],
                currentPriceCents: $data['currentPrice'],
                condition: $data['condition'],
            );

            $repository->add($bike);

            return $this->redirectToRoute('bike_list');
        }

        return $this->render('bike/new.html.twig', ['form' => $form]);
    }

    #[Route('/bikes/{id}', name: 'bike_detail')]
    public function detail(string $id, BikeRepository $repository): Response
    {
        $bike = $repository->find($id);

        if ($bike === null) {
            throw $this->createNotFoundException('Bike not found.');
        }

        return $this->render('bike/detail.html.twig', ['bike' => $bike]);
    }
}