# Retryer

Simple retryer

Usage:
```php
	$result = (new \Retryer\Retryer())
		/** @var callable $action Execute some action */
		->do($action)
		/** @var int $N N times, 1 by default */
		->times($N)
		/** @var int $T with delay T ms between iterations, 0 by default */
		->withDelay($T)
		/** @var bool $isOn = true use exponential backoff between iterations */
		->useExponentialBackoff($isOn) //
		/** @var float $M use linear backoff M multiplier */
		->useLinearBackoffMultiplier($M) //
		/** @note use can use only linear or exponential backoff, by default backoff is constant and equals to T */
		/** @var string[] $exceptions Array of exceptions' FQCN which may interrupt execution */
		->withBreakingExceptions($exceptions)
		/** @note throw exception on last try if failed */
		->throwExceptionOnLastTry()
		->execute();
```

## Disclaimer

All information and source code are provided AS-IS, without express or implied warranties.
Use of the source code or parts of it is at your sole discretion and risk.
Citymobil LLC takes reasonable measures to ensure the relevance of the information posted in this repository, but it does not assume responsibility for maintaining or updating this repository or its parts outside the framework established by the company independently and without notifying third parties.


Вся информация и исходный код предоставляются в исходном виде, без явно выраженных или подразумеваемых гарантий. Использование исходного кода или его части осуществляются исключительно по вашему усмотрению и на ваш риск. Компания ООО "Ситимобил" принимает разумные меры для обеспечения актуальности информации, размещенной в данном репозитории, но она не принимает на себя ответственности за поддержку или актуализацию данного репозитория или его частей вне рамок, устанавливаемых компанией самостоятельно и без уведомления третьих лиц.
