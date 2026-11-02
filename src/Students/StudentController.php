<?php

declare(strict_types=1);

namespace App\Students;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

final class StudentController
{
    public function __construct(
        private readonly StudentRepository $students,
        private readonly StudentValidator $validator,
    ) {
    }

    public function index(Request $request): Response
    {
        $term = $request->queryString('search');
        $city = $request->queryString('city');
        if ($term !== null && mb_strlen($term) > 100) {
            throw new HttpException(422, 'Invalid query parameter.', ['search' => ['Must be at most 100 characters.']]);
        }
        $page = $request->queryInt('page', 1, 1, 100000);
        $perPage = $request->queryInt('per_page', 20, 1, 100);
        $total = $this->students->count($term, $city);

        return Response::json([
            'data' => $this->students->search($term, $city, $perPage, ($page - 1) * $perPage),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    public function show(Request $request, array $params): Response
    {
        return Response::json(['data' => $this->findOrFail($params['id'])]);
    }

    public function store(Request $request): Response
    {
        $body = $request->json();

        if (is_array($body) && $body !== [] && array_is_list($body)) {
            $created = $this->students->createMany($this->validator->validateMany($body));

            return Response::json(['data' => $created, 'meta' => ['created' => count($created)]], 201);
        }

        $student = $this->students->create($this->validator->validate($body));

        return Response::json(['data' => $student], 201)->withHeaders(['Location' => "/students/{$student['id']}"]);
    }

    public function update(Request $request, array $params): Response
    {
        $this->findOrFail($params['id']);
        $data = $this->validator->validate($request->json(), partial: true);

        return Response::json(['data' => $this->students->update($params['id'], $data)]);
    }

    public function destroy(Request $request, array $params): Response
    {
        if (!$this->students->delete($params['id'])) {
            throw new HttpException(404, 'Student not found.');
        }

        return Response::noContent();
    }

    public function history(Request $request, array $params): Response
    {
        $history = $this->students->history($params['id']);
        if ($history === []) {
            throw new HttpException(404, 'Student not found.');
        }

        return Response::json(['data' => $history]);
    }

    public function citySummary(): Response
    {
        return Response::json(['data' => $this->students->citySummary()]);
    }

    private function findOrFail(int $id): array
    {
        return $this->students->find($id) ?? throw new HttpException(404, 'Student not found.');
    }
}
