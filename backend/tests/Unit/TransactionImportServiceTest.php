<?php

namespace Tests\Unit;

//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Services\TransactionImportService;
use Exception;

/**
 * Klasa pomocnicza, która "otwiera" metody protected do celów testowych.
 */
class TestableTransactionImportService extends TransactionImportService
{
    public function exposeParserStrategy(string $extension)
    {
        return $this->getParserStrategy($extension);
    }

    public function exposeValidateRecord(array $record): \Illuminate\Contracts\Validation\Validator
    {
        return $this->validateRecord($record);
    }
}

class TransactionImportServiceTest extends TestCase
{
    private TestableTransactionImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Inicjalizujemy nasz odblokowany serwis przed każdym testem
        $this->service = new TestableTransactionImportService();
    }


    // 1-testy fabryki getParserStrategy

    /**
     * Czy fabryka poprawnie tworzy odpowiednią strategię dla obsługiwanego rozszerzenia.
     */
    public function test_factory_resolves_correct_strategy_classes(): void
    {
        $jsonStrategy = $this->service->exposeParserStrategy('json');
        $csvStrategy = $this->service->exposeParserStrategy('csv');

        $this->assertInstanceOf(\App\Services\Strategies\JsonParseStrategy::class, $jsonStrategy);
        $this->assertInstanceOf(\App\Services\Strategies\CsvParseStrategy::class, $csvStrategy);
    }

    /**
     *  Czy fabryka rzuca wyjątek, gdy dostanie nieobsługiwany format.
     */
    public function test_factory_throws_exception_for_unsupported_extension(): void
    {
        // Oczekujemy Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unsupported file extension layout: pdf");

        //zły format
        $this->service->exposeParserStrategy('pdf');
    }

   
    // 2-testy walidacji validateRecord

    /**
     * Test poprawnego rekordu
     */
    public function test_validation_passes_for_valid_record(): void
    {
        $validRecord = [
            'transaction_id'   => 'TXN-100',
            'account_number'   => 'PL12345678901234567890123456',
            'transaction_date' => '2026-07-06',
            'amount'           => '500.25',
            'currency'         => 'PLN'
        ];

        $validator = $this->service->exposeValidateRecord($validRecord);

        $this->assertFalse($validator->fails(), "Walidacja powinna przejść bez błędów dla poprawnych danych.");
    }

    /**
     * Test nie poprawnych danych
     */
    public function test_validation_fails_for_invalid_amount_and_account(): void
    {
        $invalidRecord = [
            'transaction_id'   => 'TXN-100',
            'account_number'   => 'ZLY-IBAN-123', // Nie przejdzie regexu
            'transaction_date' => '2026-07-06',
            'amount'           => '-50.00', // Kwota ujemna, niedozwolona
            'currency'         => 'PLN'
        ];

        $validator = $this->service->exposeValidateRecord($invalidRecord);

        $this->assertTrue($validator->fails(), "Walidacja powinna zgłosić błędy.");
        
        // Sprawdzamy czy konkretne pola wygenerowały błędy
        $errors = $validator->errors();
        $this->assertTrue($errors->has('amount'), "Powinien wystąpić błąd dla ujemnej kwoty.");
        $this->assertTrue($errors->has('account_number'), "Powinien wystąpić błąd dla złego formatu konta.");
    }

    /**
     * Test braku wymaganych pól.
     */
    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $emptyRecord = []; // Całkowicie pusta tablica

        $validator = $this->service->exposeValidateRecord($emptyRecord);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('transaction_id'));
        $this->assertTrue($validator->errors()->has('amount'));
    }
}