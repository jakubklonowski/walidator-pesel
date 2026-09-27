# Walidator PESEL

Walidacja polskiego numeru PESEL w PHP 8.2 i Symfony 5.4.
Domena została napisana w czystym PHP, powstała komenda konsolowa oraz constraint `#[Pesel]` dla komponentu Validator.

Algorytm jest niewielki, pozostała część repozytorium to decyzje dotyczące architektury, środowiska uruchomieniowego 
i narzędzi (PHPUnit, PHPStan, PHP CS Fixer). Projekt został zrealizowany na wyrost, względem rozmiaru problemu, w celu
zademonstrowania umiejętności.

Miejsca do których najbardziej warto zajrzeć:
- [Decyzje projektowe](#decyzje-projektowe)
- [Jak uruchomić projekt](#start)

Projekt zrealizowałem w języku angielskim, README jest po polsku.

Projekt wykonał Jakub Klonowski.

## Start

### Wymagania

Wymagany jest wyłącznie Docker.

### Uruchomienie projektu

Sklonuj repozytorium
```bash
git clone https://github.com/jakubklonowski/walidator-pesel
```

Wejdź do katalogu
```bash
cd walidator-pesel
```

Uruchom program w jednej z dwóch poniższych konfiguracji. 
Pierwsza to uruchomienie polecenia `composer qa` za którym kryją się 4 kroki sprawdzające poprawność kodu i programu. 
Opisane są dokładniej w sekcji [Kontrola jakości](#kontrola-jakości).
Druga to standardowe działanie (walidacja numeru PESEL).

Pierwsze uruchomienie sklonowanego repozytorium zbuduje obraz, zainstaluje zależności (poprzez `docker/entrypoint.sh`).
Kolejne uruchomienia startują od razu.

Sprawdzenia jakości kodu i programu:
```bash
docker compose run --rm app composer qa
```

Walidacja numeru PESEL:
```bash
docker compose run --rm app bin/console app:pesel:validate 44051401359
```
Wynik:
```
 [OK] 44*******59 is a valid PESEL number.

 --------------- ------------
  Date of birth   1944-05-14
  Gender          male
 --------------- ------------
```

Walidacja niepoprawnego numeru PESEL kończy się kodem wyjścia `1` i listą wszystkich niespełnionych warunków walidacji:
```bash
docker compose run --rm app bin/console app:pesel:validate 00022900011
```
Wynik:
```
 [ERROR] 00*******11 is not a valid PESEL number.

 * [invalid_birth_date] The first six digits do not encode an existing date of birth.
 * [invalid_checksum] The checksum digit does not match the preceding ten digits.
```

Sprawdzenie środowiska projektu - PHP 8.2 i Symfony 5.4, zgodnie z poleceniem:
```bash
docker compose run --rm app php -v
docker compose run --rm app bin/console --version
```

## Algorytm walidacji

Cechy numeru PESEL sprawdzane w tym projekcie:
- 11 cyfr
- 6 cyfr daty urodzenia `YYMMDD`
- 4 cyfry numeru porządkowego
- jedna cyfra kontrolna
- stulecie zapisywane w polu miesiąca

### Szczegóły standardu PESEL

#### Stulecie zapisywane w polu miesiąca
| Pole miesiąca | Lata |
|---|---|
| 81–92 | 1800–1899 |
| 01–12 | 1900–1999 |
| 21–32 | 2000–2099 |
| 41–52 | 2100–2199 |
| 61–72 | 2200–2299 |

#### Cyfra kontrolna
Wagi `1,3,7,9,1,3,7,9,1,3` nakładane na pierwsze dziesięć
cyfr, następnie `(10 − suma % 10) % 10`.

#### Płeć
Określana przez dziesiątą cyfrę — parzysta oznacza kobietę, nieparzysta mężczyznę.

#### Rok przestępny
Jedynie w latach przestępnych prawidłową datą jest 29 lutego.
Ma to znaczenie przy dekodowaniu daty bo `DateTimeImmutable::createFromFormat()` przy 
podaniu nieprawidłowej daty przesuwa wynik o odpowiednią ilość dni (na 1 marzec) i nie zwraca błędu.

O tym, czy rok jest przestępny, decyduje stulecie zakodowane w polu miesiąca.
Widać to na parze numerów różniących się wyłącznie tym polem:

| Numer | Data | Wynik |
|---|---|---|
| `00222900016` | 2000-02-29 | **poprawny** — rok 2000 jest przestępny |
| `00022900010` | 1900-02-29 | **niepoprawny** — rok 1900 nie jest przestępny |

Oba numery mają poprawną cyfrę kontrolną, więc walidator sprawdzający wyłącznie
sumę kontrolną przyjąłby oba.

#### Suma kontrolna
Suma kontrolna wykrywa każdą zmianę pojedynczej cyfry, ale nie wykrywa niektórych przestawień cyfr, 
nie wpływa również na poprawność zakodowanych dat urodzenia. 
Jest to ograniczenie standardu PESEL i nie wynika z działania walidatora.

## Architektura

```
wejście
  └─ walidacja struktury: LengthAndDigitsRule ── błąd ──► jedno naruszenie, koniec
       └─ walidacja semantyki: BirthDateRule, ChecksumRule ──► wszystkie naruszenia
            └─ ValidationResult
                 ├─ app:pesel:validate    wypisuje wszystkie naruszenia
                 ├─ #[Pesel]              jedno naruszenie z kodem pierwszego
                 └─ Pesel::fromString()   wyjątek z kodem pierwszego
```

Katalog `src/Pesel/` nie importuje niczego z Symfony. Framework występuje wyłącznie w dwóch 
adapterach, które tłumaczą wynik domeny na swój język. Żaden z nich nie zawiera logiki PESEL-u.
Powodem rozdzielenia domeny walidatora od Symfony jest umożliwienie wykorzystania reguł walidacji
tam, gdzie Symfony nie jest dostępny, np. w module PrestaShop działającym w warstwie legacy, w cronie lub w innym frameworku. Drugim powodem jest uproszczenie testowania - nie trzeba uruchamiać kernela.
Granica między walidatorem a Symfony jest egzekwowana poprzez `composer layers`.

Według powyższego diagramu wynik jest zwracany na różne sposoby różnym konsumentom 
mimo wykorzystania tego samego walidatora - przyczyna jest opisana w sekcji [Decyzje projektowe](#decyzje-projektowe)

## Użycie

Jako constraint:

```php
use App\Validator\Constraints\Pesel;
use Symfony\Component\Validator\Constraints\NotBlank;

final class RegistrationInput
{
    #[NotBlank]
    #[Pesel]
    public string $pesel = '';
}
```

Naruszenie niesie kod przyczyny (`invalid_length`, `not_digits`, `invalid_birth_date`, `invalid_checksum`) 
dostępny przez `ConstraintViolation::getCode()`, więc warstwa prezentacji może rozróżnić przyczyny bez 
parsowania komunikatów. Komunikat można nadpisać: `#[Pesel(['message' => 'Niepoprawny numer PESEL: {{ value }}.'])]`.

Bezpośrednio, bez frameworka:

```php
use App\Pesel\Pesel;
use App\Pesel\Validation\PeselValidator;

$result = PeselValidator::default()->validate($input);

foreach ($result->violations() as $violation) {
    echo $violation->code->value, ': ', $violation->message, PHP_EOL;
}

$pesel = Pesel::fromString('44051401359');
$pesel->birthDate()->format('Y-m-d');
$pesel->gender();
```

`Pesel::fromString()` dla niepoprawnego numeru wyrzuca `InvalidPeselException` z polem `violationCode`.

## Decyzje projektowe

### Domena

**Jedno źródło reguł dla trzech konsumentów.** Invariant value object nie
potrafi zwrócić wszystkich naruszeń naraz, a walidator zwracający listę nie daje
gwarancji takich jak invariant. `PeselValidator::validate()` zwraca listę naruszeń,
a `Pesel::fromString()` wywołuje ten sam walidator i wyrzuca wyjątek po napotkaniu pierwszego. 
Każdy konsument bierze tyle ile potrzebuje: komenda — pełną diagnozę,
constraint i value object — pierwszą przyczynę. Reguły są zapisane raz.

**Dwie fazy walidacji.** Nie da się sensownie policzyć cyfry kontrolnej ciągu znaków, 
który nie ma jedenastu cyfr. Faza strukturalna jest więc bramką: jeśli
zawiedzie, walidacja kończy się jednym naruszeniem. Dopiero po niej reguły
semantyczne `BirthDateRule` i `ChecksumRule` wykonują się razem i obie mogą raportować. 
Podział widać w konstruktorze `PeselValidator(iterable $structuralRules, iterable $semanticRules)`, 
a nie w kolejności elementów jednej tablicy.

**Value object zawsze poprawny.** Prywatny konstruktor, jedyną drogą utworzenia
jest `Pesel::fromString()`. Istniejący obiekt `Pesel` jest poprawny z definicji,
więc kod, który go przyjmuje, nie musi niczego sprawdzać ponownie.
`final readonly class` sprawia, że niezmienność gwarantuje język.

**Reguła zwraca `?Violation`, nie tablicę.** PHPStan na poziomie `max` wymaga
udokumentowanego typu wartości każdej tablicy w sygnaturze, a PHP nie ma typów generycznych.
Zamiast obkładać kod adnotacjami, tablice zostały wyprojektowane poza sygnatury:
każda reguła wykrywa co najwyżej jeden problem, a `ValidationResult` przyjmuje
argument wariadyczny, który PHPStan rozumie natywnie. W całej domenie zostało sześć
adnotacji typu, w dwóch klasach.

**`checkdate()` zamiast parsera dat.**
`DateTimeImmutable::createFromFormat('Ymd', '20230230')` nie zwraca `false` —
zwraca 2 marca 2023, a ślad problemu zostaje wyłącznie jako ostrzeżenie
w `getLastErrors()`. 
Data jest najpierw sprawdzana przez `checkdate()`, a `DateTimeImmutable`
powstaje dopiero po jej akceptacji: z formatem `!Y-m-d` (północ zamiast bieżącej
godziny) i jawną strefą UTC (niezależność od `date.timezone`).

**Maskowanie numeru.** PESEL jest daną osobową, a komunikaty błędów trafiają do
logów. Komunikat wyjątku, komunikat naruszenia i wyjście komendy zawierają
wyłącznie formę `44*******59`. Wyjątkiem jest `ConstraintViolation::getInvalidValue()`, 
które zgodnie z kontraktem Symfony zwraca oryginalną wartość — wywołujący i tak ją posiada, 
a maskowanie dotyczy tego, co jest renderowane i logowane.

**Klasy `final` i `declare(strict_types=1)` w każdym pliku.** Rozszerzanie przez
kompozycję, nie dziedziczenie: nowa reguła to nowa implementacja `PeselRule`
dopisana w `PeselValidator::default()`.

### Integracja z Symfony

**Jedno naruszenie reguł walidacji na wartość.** Constraint zgłasza tylko pierwsze 
naruszenie, z kodem przyczyny — tak jak wbudowane walidatory o wielu możliwych przyczynach,
np. `IbanValidator`. Formularz pokazuje jeden komunikat zamiast kilku identycznych,
a przyczyna pozostaje dostępna przez `getCode()`.

**Konwencje komponentu Validator.** Pusta wartość (`null`, `''`) przechodzi,
bo za wymagalność odpowiada `#[NotBlank]`. Wartość niebędąca napisem kończy się
`UnexpectedValueException`, jak we wbudowanych walidatorach.

**Rejestracja przez fabrykę.** `config/services.yaml` tworzy domenowy walidator
przez `PeselValidator::default()`, więc kontener DI i value object korzystają z tej
samej kompozycji reguł. Zrezygnowałem z `!tagged_iterator` bo podział walidacji na
fazy i ich kolejność mają znaczenie, a `!tagged_iterator` by to utrudnił.

**Szkielet bez warstwy HTTP.** Projekt jest aplikacją konsolową, więc front
controller, routing i kontroler zostały usunięte ze szkieletu. Serwer WWW bez
endpointów byłby atrapą — stąd też brak nginx i php-fpm.

### Środowisko uruchomieniowe

**Docker jako gwarancja wersji.** Nie ma potrzeby konfigurować zależności, wystaczy docker.

**`docker compose run --rm` zamiast `up` + `exec`.** Aplikacja nie ma procesu
długo żyjącego — kontener podniesiony przez `up` zakończyłby się od razu.
`run --rm` tworzy kontener na czas jednej komendy i sprząta po sobie.

**Jedna komenda przygotowuje środowisko po sklonowaniu repo.** Entrypoint sprawdza obecność
pliku `vendor/autoload.php` i w razie jego braku uruchamia `composer install`, komunikując to użytkownikowi.

**Użytkownik nie jest rootem.** Procesy w kontenerze nie działają jako root.

**`vendor/` i `var/` w woluminach nazwanych.** Kontener nie zapisuje niczego
w katalogu projektu, więc UID właściciela katalogu na hoście nie ma znaczenia.

**Konflikt znaków końca linii.** Projekt tworzyłem na Windows, a kontener jest na Linuxie stąd różne znaki 
końca wiersza uniemożliwiłyby działanie skryptu shella `docker/entrypoint.sh`. Rozwiązaniem jest `.gitattributes` z `eol=lf` w pierwszym commicie.

**Zależności budowane pod PHP 8.2.** `config.platform.php = 8.2.0` w `composer.json`: wersje pakietów w `composer.lock` są budowane pod PHP 8.2 niezależnie od wersji PHP, na której uruchomiono Composera.

**Konflikt właściciela projektu i właściciela procesu.** `safe.directory` gita w obrazie: katalog projektu ma innego właściciela niż proces, a composer odczytuje z gita wersję projektu. Bez tej opcji występował konflikt.

**Krótszy czas budowania obrazu** `.dockerignore` skraca czas budowania obrazu.

### Jakość (QA)

**PHPStan na poziomie `max`** z rozszerzeniem `phpstan-phpunit`, które rozumie
asercje zawężające typy w testach.

**PHP CS Fixer** z `@Symfony` jako stylem bazowym, `@PHP8x2Migration` (składnia
dostępna w PHP 8.2) i trzema regułami: `declare_strict_types`, `strict_comparison` 
i `strict_param`.

**Granica warstw.** `composer layers` - import to praktycznie jedyna droga, którą 
zależność frameworka dostaje się do klasy, a grep to opcja niewymagająca konfiguracji. 
Alternatywny deptrac byłby nieproporcjonalny dla tego projektu.

**PHPUnit 9.6.** Wybrany "na wszelki wypadek" w tej wersji bo to była wersja współczesna wobec Symfony 5.4.

### Testy

- **Warstwy testowane osobno.** Domena czystym PHPUnitem, każda reguła w izolacji.
  Constraint przez `ConstraintValidatorTestCase` — standardowe narzędzie Symfony
  dla walidatorów. Komenda przez `KernelTestCase` i `CommandTester` na prawdziwym
  kontenerze DI, więc test sprawdza przy okazji konfigurację `services.yaml`.
- **Przypadki nazwane jak specyfikacja.** Data providery mają opisowe klucze
  (`'29 February 1900, not a leap year'`, `'month 13, inside the century gap'`),
  więc raport PHPUnit czyta się jak listę wymagań.
- **Każda asercja wykrywa konkretny błąd.** Test komendy sprawdza `Gender\s+male\b`, 
  bo samo `male` przeszłoby także dla `female`, a test strefy czasowej ustawia na czas 
  testu strefę inną niż UTC, żeby nie przechodził dzięki konfiguracji w `php.ini`.
- **Prywatność pod testem.** Każdy kanał wyjścia — wyjątek, naruszenie constraintu lub
  komenda (dla numeru poprawnego i niepoprawnego) sprawdza czy pełny numer PESEL nie wyciekł w wyniku.

## Świadomie pominięte

- **Odrzucanie dat przyszłych.** Algorytmicznie poprawne; ich odrzucanie jest regułą
  biznesową, nie elementem specyfikacji PESEL.
- **Odrzucanie lat 2100–2299.** Jak wyżej: legalne w algorytmie, praktycznie
  niewydawane. Numer `00410100017` jest akceptowany jako 2100-01-01.
- **CI, mutation testing, deptrac.** Wartościowe, ale nieproporcjonalne do rozmiaru
  problemu. Jakości pilnuje jedno polecenie opisane w sekcji poniżej.

## Kontrola jakości

```bash
docker compose run --rm app composer qa
```

Na `qa` składają się cztery kroki, każdy dostępny osobno:

| Komenda | Zakres |
|---|---|
| `composer test` | PHPUnit — 66 testów, 227 asercji |
| `composer stan` | PHPStan na poziomie `max` |
| `composer cs` | PHP CS Fixer w trybie kontroli |
| `composer layers` | brak `use Symfony\` w `src/Pesel/` |
