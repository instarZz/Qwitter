<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentFormType;
use App\Form\PostType;
use App\Repository\PostRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/post')]
class PostController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_USER', statusCode: 401, message: 'You have to be logged-in to access this ressource')]
    #[Route('/new', name: 'post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Security $security): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->security->getUser();
            $post->setAuthor($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('post_index');
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'post_show', methods: ['GET', 'POST'])]
    public function show(Post $post, Request $request): Response
    {
        $comments = $post->getComments();
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->security->getUser();
            $comment->setAuthor($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $post->addComment($comment);

            $entityManager->flush();

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_USER', statusCode: 401, message: 'You have to be logged-in to access this ressource')]
    #[Route('/{id}/edit', name: 'post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post): Response
    {
        $user = $this->security->getUser();
        if ($user === $post->getAuthor()) {
            $form = $this->createForm(PostType::class, $post);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('post_index');
            }

            return $this->render('post/edit.html.twig', [
                'post' => $post,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('common/error.html.twig', [
            'error' => 401,
            'message' => 'Unauthorized access',
        ]);
    }

    #[IsGranted('ROLE_USER', statusCode: 401, message: 'You have to be logged-in to access this ressource')]
    #[Route('/{id}', name: 'post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post): Response
    {
        $user = $this->security->getUser();
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_index');
    }

    #[IsGranted('ROLE_USER', statusCode: 401, message: 'You have to be logged-in to access this ressource')]
    #[Route('/like/{id}', name: 'post_like')]
    public function like(Post $post): JsonResponse
    {
        if ($post->getLikes()->contains($this->getUser())) {
            $post->removeLike($this->getUser());
        }
        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'code' => 200,
            'likes' => count($post->getLikes()),
            'liked' => false,
        ]);

        $post->addLike($this->getUser());
        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'code' => 200,
            'likes' => count($post->getLikes()),
            'liked' => true,
        ]);
    }
}
