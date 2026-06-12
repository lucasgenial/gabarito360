import 'package:flutter/material.dart';

import 'g360_tokens.dart';

abstract final class G360Theme {
  static ThemeData light() => _theme(
    brightness: Brightness.light,
    body: G360Tokens.lightBody,
    surface: G360Tokens.lightSurface,
    text: G360Tokens.lightText,
    muted: G360Tokens.lightMuted,
    border: G360Tokens.lightBorder,
    primary: G360Tokens.primary,
  );

  static ThemeData dark() => _theme(
    brightness: Brightness.dark,
    body: G360Tokens.darkBody,
    surface: G360Tokens.darkSurface,
    text: G360Tokens.darkText,
    muted: G360Tokens.darkMuted,
    border: G360Tokens.darkBorder,
    primary: G360Tokens.darkPrimary,
  );

  static ThemeData _theme({
    required Brightness brightness,
    required Color body,
    required Color surface,
    required Color text,
    required Color muted,
    required Color border,
    required Color primary,
  }) {
    final scheme = ColorScheme.fromSeed(
      seedColor: primary,
      brightness: brightness,
      surface: surface,
      error: G360Tokens.danger,
    );

    return ThemeData(
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: body,
      fontFamily: 'Verdana',
      appBarTheme: AppBarTheme(backgroundColor: surface, foregroundColor: text),
      cardTheme: CardThemeData(
        color: surface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(G360Tokens.radiusLg),
          side: BorderSide(color: border),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surface,
        constraints: const BoxConstraints(minHeight: G360Tokens.controlHeight),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(G360Tokens.radiusMd),
          borderSide: BorderSide(color: border),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          minimumSize: const Size.fromHeight(G360Tokens.controlHeight),
          backgroundColor: primary,
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(G360Tokens.radiusMd)),
        ),
      ),
      textTheme: ThemeData(brightness: brightness).textTheme.apply(bodyColor: text, displayColor: text),
      dividerColor: border,
      hintColor: muted,
    );
  }
}
